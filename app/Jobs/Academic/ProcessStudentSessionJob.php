<?php

namespace App\Jobs\Academic;

use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Services\Finance\FeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessStudentSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $student;
    protected $session;
    protected $semesterName;

    /**
     * Create a new job instance.
     */
    public function __construct(Student $student, Session $session, $semesterName)
    {
        $this->student = $student;
        $this->session = $session;
        $this->semesterName = $semesterName;
    }

    /**
     * Execute the job.
     */
    public function handle(FeeService $feeService): void
    {
        DB::transaction(function () use ($feeService) {
            $existingSession = StudentSession::where('student_id', $this->student->id)
                ->where('session_id', $this->session->id)
                ->first();

            if ($existingSession) {
                StudentSession::where('student_id', $this->student->id)
                    ->where('id', '!=', $existingSession->id)
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);

                $existingSession->update(['status' => 'active']);
                $this->student->update([
                    'current_level' => $existingSession->level,
                    'status' => 'active',
                ]);
            } else {
                if ($this->student->status === 'graduated') {
                    return;
                }

                // Check pending school fees setting
                $promotePending = filter_var(\App\Models\SystemSetting::get('promote_pending_payments', false), FILTER_VALIDATE_BOOLEAN);
                
                if (!$promotePending) {
                    $previousSession = Session::where('start_date', '<', $this->session->start_date)
                        ->where('academic_stream', $this->session->academic_stream ?? 'UG')
                        ->where('calendar_type', $this->session->calendar_type ?? 'semester')
                        ->orderBy('start_date', 'desc')
                        ->first();
                        
                    if ($previousSession) {
                        $hasUnpaidFees = \App\Models\Invoice::where('user_id', $this->student->user_id)
                            ->where('session_id', $previousSession->id)
                            ->where('type', 'school_fee')
                            ->where('status', '!=', 'paid')
                            ->exists();
                            
                        if ($hasUnpaidFees) {
                            $this->student->update([
                                'pending_promotion_session_id' => $this->session->id
                            ]);
                            return;
                        }
                    }
                }

                $newLevel = (int) $this->student->current_level;
                $isGraduating = false;
                $isExtension = false;

                if ($this->session->type === 'regular') {
                    if ($this->student->isPostgraduate()) {
                        // Postgraduate students stay at their fixed tier level (700 PGD, 800 Master's, 900 Ph.D)
                        $newLevel = (int) $this->student->current_level ?: 800;

                        $hasCompletedDefense = $this->student->latestResearchProject()
                            ->where('status', 'completed')
                            ->exists();

                        $minGraduationCgpa = (float) ($this->student->program?->min_graduation_cgpa ?? 3.00);
                        $currentCgpa = app(\App\Services\Postgraduate\PostgraduateResearchService::class)
                            ->calculateStudentCgpa($this->student);

                        if ($hasCompletedDefense && $currentCgpa >= $minGraduationCgpa) {
                            $isGraduating = true;
                            $this->student->update([
                                'status' => 'graduated',
                            ]);
                            $this->student->pgProfile?->update([
                                'candidacy_status' => 'graduated',
                            ]);
                        } else {
                            $pgProfile = $this->student->pgProfile;
                            if ($pgProfile) {
                                $nextEnrolledCount = ((int) $pgProfile->sessions_enrolled_count) + 1;
                                $normalDurationSessions = max((int) ($this->student->program_duration ?: 2), 1);
                                $isExtension = $nextEnrolledCount > $normalDurationSessions;

                                $pgProfile->update([
                                    'sessions_enrolled_count' => $nextEnrolledCount,
                                    'candidacy_status' => $isExtension ? 'extension' : $pgProfile->candidacy_status,
                                ]);
                            }

                            $this->student->update([
                                'current_level' => $newLevel,
                                'status' => 'active',
                            ]);
                        }
                    } else {
                        $programDuration = (int) ($this->student->program?->duration ?: ($this->student->program_duration ?: 4));
                        $maxLevel = max(400, $programDuration * 100);

                        if ($this->student->current_level >= $maxLevel) {
                            $isGraduating = true;
                            $this->student->update([
                                'status' => 'graduated',
                            ]);
                        } else {
                            $newLevel += 100;
                            $this->student->update([
                                'current_level' => $newLevel,
                                'status' => 'active',
                            ]);
                        }
                    }
                }

                if (!$isGraduating) {
                    StudentSession::where('student_id', $this->student->id)
                        ->where('status', 'active')
                        ->update(['status' => 'completed']);

                    StudentSession::create([
                        'student_id' => $this->student->id,
                        'session_id' => $this->session->id,
                        'level' => $newLevel,
                        'status' => 'active',
                        'semester' => $this->semesterName,
                    ]);

                    if ($isExtension) {
                        $feeService->generateMilestoneFeeInvoice($this->student, $this->session, 'extension_fee');
                    }
                }
            }

            // After promotion/activation, automatically generate invoice
            if ($this->student->status !== 'graduated') {
                $feeService->generateSchoolFeeInvoice($this->student, $this->session);
            }
        });
    }
}
