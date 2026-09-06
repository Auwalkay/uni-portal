<?php

namespace App\Services;

use App\Models\CourseRegistration;
use App\Models\ExamAttendance;
use App\Models\ExamSchedule;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ExamManagementService
{
    /**
     * Detect hall venue conflicts efficiently for a given academic session.
     */
    public function detectVenueConflicts(?string $sessionId = null): array
    {
        $conflicts = [];
        
        $conflictCandidates = DB::table('exam_schedules')
            ->select('venue', 'exam_date')
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->groupBy('venue', 'exam_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($conflictCandidates as $cand) {
            $schedulesAtVenue = ExamSchedule::where('venue', $cand->venue)
                ->whereDate('exam_date', $cand->exam_date)
                ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
                ->get();

            $count = count($schedulesAtVenue);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $s1 = $schedulesAtVenue[$i];
                    $s2 = $schedulesAtVenue[$j];
                    if (($s1->start_time < $s2->end_time) && ($s1->end_time > $s2->start_time)) {
                        $conflicts[] = [
                            'venue' => $s1->venue,
                            'date' => $s1->exam_date ? $s1->exam_date->format('Y-m-d') : '',
                            'exam1' => $s1->reference_id,
                            'exam2' => $s2->reference_id,
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Verify candidate pass token or matric number and return candidate profile, fee clearance, and registered exam schedules.
     */
    public function verifyStudentPass(string $token): ?array
    {
        $currentSession = AcademicCacheService::getCurrentSession();

        // Search student by token, matriculation number, or user ID
        $student = Student::where('matriculation_number', $token)
            ->orWhere('id', $token)
            ->orWhere('matriculation_number', 'like', "%{$token}%")
            ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$token}%"))
            ->with(['user', 'department', 'programme'])
            ->first();

        if (! $student) {
            return null;
        }

        // Fetch student registered course IDs for the session
        $registeredCourseIds = CourseRegistration::where('student_id', $student->id)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->pluck('course_id');

        // Fetch exam schedules with attendance logs for this candidate
        $schedules = ExamSchedule::with(['course', 'department', 'attendances' => function ($q) use ($student) {
            $q->where('student_id', $student->id);
        }])
            ->whereIn('course_id', $registeredCourseIds)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // Fee clearance check (unpaid invoices check)
        $pendingInvoices = Invoice::where('user_id', $student->user_id)
            ->where('status', 'unpaid')
            ->count();
            
        $isCleared = ($pendingInvoices === 0 && $registeredCourseIds->count() > 0);

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name ?? 'Candidate',
                'matric_number' => $student->matric_number ?? 'N/A',
                'department' => $student->department?->name ?? 'N/A',
                'programme' => $student->programme?->name ?? 'N/A',
                'level' => $student->current_level ?? '100',
                'passport_photo' => $student->passport_photo_path ? asset('storage/' . $student->passport_photo_path) : null,
            ],
            'is_cleared' => $isCleared,
            'pending_invoices' => $pendingInvoices,
            'schedules' => $schedules,
            'registered_course_ids' => $registeredCourseIds->toArray(),
            'token' => $token,
        ];
    }

    /**
     * Record candidate exam attendance with strict course registration validation.
     */
    public function recordAttendance(string $scheduleId, string $studentId, ?string $status = 'present', ?string $notes = null): ExamAttendance
    {
        $schedule = ExamSchedule::with('course')->findOrFail($scheduleId);

        // Strict Validation: Ensure candidate is registered for this course
        $isRegistered = CourseRegistration::where('student_id', $studentId)
            ->where('course_id', $schedule->course_id)
            ->where('session_id', $schedule->session_id)
            ->exists();

        if (! $isRegistered) {
            throw new \InvalidArgumentException("Candidate is NOT registered for course {$schedule->course?->code}!");
        }

        return ExamAttendance::updateOrCreate(
            [
                'exam_schedule_id' => $scheduleId,
                'student_id' => $studentId,
            ],
            [
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'status' => $status ?? 'present',
                'notes' => $notes ?? null,
            ]
        );
    }
}
