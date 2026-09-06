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

                $newLevel = $this->student->current_level;
                $isGraduating = false;

                if ($this->session->type === 'regular') {
                    $maxLevel = $this->student->program_duration * 100;

                    if ($this->student->current_level >= $maxLevel) {
                        $isGraduating = true;
                        $this->student->update([
                            'status' => 'graduated',
                            'graduated_at' => now(),
                        ]);
                    } else {
                        $newLevel += 100;
                        $this->student->update([
                            'current_level' => $newLevel,
                            'status' => 'active'
                        ]);
                    }
                }

                if (!$isGraduating) {
                    StudentSession::create([
                        'student_id' => $this->student->id,
                        'session_id' => $this->session->id,
                        'level' => $newLevel,
                        'status' => 'active',
                        'semester' => $this->semesterName,
                    ]);
                }
            }

            // After promotion/activation, automatically generate invoice
            if ($this->student->status !== 'graduated') {
                $feeService->generateSchoolFeeInvoice($this->student, $this->session);
            }
        });
    }
}
