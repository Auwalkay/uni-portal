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
     * Detect hall venue conflicts efficiently for a given academic session and semester.
     */
    public function detectVenueConflicts(?string $sessionId = null, ?string $semesterId = null): array
    {
        $conflicts = [];
        
        $conflictCandidateKeys = DB::table('exam_schedules')
            ->select('venue', 'exam_date')
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->groupBy('venue', 'exam_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($conflictCandidateKeys->isEmpty()) {
            return $conflicts;
        }

        $venues = $conflictCandidateKeys->pluck('venue')->unique()->toArray();
        $dates = $conflictCandidateKeys->pluck('exam_date')->unique()->toArray();

        $candidateSchedules = ExamSchedule::select('id', 'reference_id', 'venue', 'exam_date', 'start_time', 'end_time', 'session_id', 'semester_id')
            ->whereIn('venue', $venues)
            ->whereIn('exam_date', $dates)
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->get()
            ->groupBy(fn ($item) => $item->venue . '|' . ($item->exam_date ? $item->exam_date->format('Y-m-d') : ''));

        foreach ($candidateSchedules as $group) {
            $count = $group->count();
            if ($count < 2) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $s1 = $group[$i];
                    $s2 = $group[$j];
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

        $currentSemester = AcademicCacheService::getCurrentSemester();

        // Fetch student registered course IDs for the session and current semester
        $registeredCourseIds = CourseRegistration::where('student_id', $student->id)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->when($currentSemester, fn ($q) => $q->where('semester_id', $currentSemester->id))
            ->pluck('course_id');

        // Fetch exam schedules with attendance logs for this candidate
        $schedules = ExamSchedule::with(['course', 'department', 'attendances' => function ($q) use ($student) {
            $q->where('student_id', $student->id);
        }])
            ->whereIn('course_id', $registeredCourseIds)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->when($currentSemester, fn ($q) => $q->where('semester_id', $currentSemester->id))
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // Fee clearance check
        $isSecondSem = $currentSemester && (stripos($currentSemester->name, 'second') !== false || $currentSemester->name == '2');

        // Auto-cancel 0-paid expired pending invoices
        Invoice::where('user_id', $student->user_id)
            ->where('status', 'pending')
            ->where('paid_amount', '<=', 0)
            ->where('due_date', '<', now())
            ->update(['status' => 'cancelled']);

        if ($isSecondSem) {
            $pendingInvoices = Invoice::where('user_id', $student->user_id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->count();

            $hasSchoolFeePaid = Invoice::where('user_id', $student->user_id)
                ->where('type', 'school_fee')
                ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
                ->where('status', 'paid')
                ->exists();

            $isFeeCleared = ($pendingInvoices === 0 && $hasSchoolFeePaid);
        } else {
            $pendingInvoices = Invoice::where('user_id', $student->user_id)
                ->whereNotIn('status', ['paid', 'partial', 'cancelled'])
                ->where('paid_amount', '<=', 0)
                ->where(function ($q) {
                    $q->whereNull('due_date')->orWhere('due_date', '>=', now());
                })
                ->count();

            $hasSchoolFeeCleared = Invoice::where('user_id', $student->user_id)
                ->where('type', 'school_fee')
                ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
                ->whereIn('status', ['paid', 'partial'])
                ->exists();

            $isFeeCleared = ($pendingInvoices === 0 && $hasSchoolFeeCleared);
        }

        $isCleared = ($isFeeCleared && $registeredCourseIds->count() > 0);

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name ?? 'Candidate',
                'matric_number' => $student->matriculation_number ?? $student->matric_number ?? 'N/A',
                'department' => $student->department?->name ?? $student->academicDepartment?->name ?? 'N/A',
                'programme' => $student->programme?->name ?? $student->program?->name ?? 'N/A',
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

        // Cross-Hall Check: Check if candidate already marked present in another venue for the same course
        $existingAttendance = ExamAttendance::whereHas('schedule', function ($q) use ($schedule) {
                $q->where('course_id', $schedule->course_id)
                  ->where('session_id', $schedule->session_id)
                  ->whereDate('exam_date', $schedule->exam_date);
            })
            ->where('student_id', $studentId)
            ->where('exam_schedule_id', '!=', $scheduleId)
            ->with('schedule')
            ->first();

        if ($existingAttendance) {
            $prevVenue = $existingAttendance->schedule?->venue ?? 'another venue';
            $prevTime = $existingAttendance->verified_at ? $existingAttendance->verified_at->format('H:i') : '';
            $notes = ($notes ? $notes . ' | ' : '') . "Note: Previously verified at {$prevVenue} ({$prevTime})";
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
