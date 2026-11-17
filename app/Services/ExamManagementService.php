<?php

namespace App\Services;

use App\Models\CourseRegistration;
use App\Models\ExamAttendance;
use App\Models\ExamSchedule;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Session;
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
     * Generate a human-readable, standard examination clearance pass token.
     * Format: EXM-{YEAR}-{SEMESTER}-{CLEAN_MATRIC}-{CHECKSUM}
     * Example: EXM-2026-S1-U25CS001-7F3A
     */
    public static function generatePassToken(Student $student, ?Session $session = null, ?Semester $semester = null): string
    {
        $session = $session ?? AcademicCacheService::getCurrentSession();
        $semester = $semester ?? AcademicCacheService::getCurrentSemester();

        // 1. Session / Year (e.g. "2025/2026" -> "2026" or current year)
        $year = now()->format('Y');
        if ($session && $session->name) {
            if (preg_match('/(\d{4})\s*\/\s*(\d{4})/', $session->name, $matches)) {
                $year = $matches[2];
            } elseif (preg_match('/\d{4}/', $session->name, $matches)) {
                $year = $matches[0];
            }
        }

        // 2. Semester code (e.g. S1 or S2)
        $semCode = 'S1';
        if ($semester && $semester->name) {
            if (stripos($semester->name, 'second') !== false || stripos($semester->name, '2') !== false) {
                $semCode = 'S2';
            } elseif (stripos($semester->name, 'summer') !== false || stripos($semester->name, '3') !== false) {
                $semCode = 'S3';
            }
        }

        // 3. Clean student matriculation number
        $rawMatric = $student->matriculation_number ?? $student->matric_number ?? '';
        $cleanMatric = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawMatric));

        if (empty($cleanMatric)) {
            $cleanMatric = 'STU' . strtoupper(substr(str_replace('-', '', $student->id), 0, 6));
        }

        // 4. 4-character cryptographic checksum
        $secretPayload = $student->id . ($session?->id ?? '') . ($semester?->id ?? '') . 'EXAM_ATTENDANCE_SECRET';
        $checksum = strtoupper(substr(hash_hmac('sha256', $secretPayload, config('app.key', 'exam-pass-secret')), 0, 4));

        return "EXM-{$year}-{$semCode}-{$cleanMatric}-{$checksum}";
    }

    /**
     * Verify candidate pass token or matric number and return candidate profile, fee clearance, and registered exam schedules.
     */
    public function verifyStudentPass(string $token, ?string $sessionId = null, ?string $semesterId = null): ?array
    {
        $token = trim($token);

        // If a full URL was passed/scanned (e.g. https://.../admin/exams/verify-pass/EXM-...), extract token
        if (str_contains($token, '/verify-pass/')) {
            $token = basename(parse_url($token, PHP_URL_PATH));
        }

        // If JSON payload was passed (from Docket QR code), extract token or matric
        if (str_starts_with($token, '{') && str_ends_with($token, '}')) {
            $decoded = json_decode($token, true);
            if (is_array($decoded)) {
                $token = $decoded['token'] ?? $decoded['matric'] ?? $token;
            }
        }

        $student = null;

        // 1. If it's a standard EXM pass token: EXM-{YEAR}-{SEM}-{MATRIC}-{CODE}
        if (str_starts_with($token, 'EXM-')) {
            $parts = explode('-', $token);
            if (count($parts) >= 4) {
                // If 5 parts: EXM, YEAR, SEM, MATRIC, CODE
                // If more parts (in case matric had hyphens): extract candidate matric segment
                $candidateMatric = (count($parts) === 5) ? $parts[3] : implode('', array_slice($parts, 3, -1));

                $student = Student::whereRaw("REPLACE(REPLACE(REPLACE(matriculation_number, '/', ''), '-', ''), ' ', '') = ?", [$candidateMatric])
                    ->orWhere('matriculation_number', $candidateMatric)
                    ->orWhere('id', 'like', "{$candidateMatric}%")
                    ->with(['user', 'department', 'programme'])
                    ->first();
            }
        }

        // 2. Direct exact or sanitized match (matriculation number, UUID, or user name)
        if (! $student) {
            $cleanToken = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $token));

            $student = Student::where('matriculation_number', $token)
                ->orWhere('id', $token)
                ->when($cleanToken, fn ($q) => $q->orWhereRaw("REPLACE(REPLACE(REPLACE(matriculation_number, '/', ''), '-', ''), ' ', '') = ?", [$cleanToken]))
                ->orWhere('matriculation_number', 'like', "%{$token}%")
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$token}%"))
                ->with(['user', 'department', 'programme'])
                ->first();
        }

        if (! $student) {
            return null;
        }

        $session = $sessionId 
            ? Session::find($sessionId) 
            : (AcademicCacheService::getCurrentSessionForStream('UG', 'semester') ?? AcademicCacheService::getCurrentSession());

        $semester = $semesterId
            ? Semester::find($semesterId)
            : ($session ? Semester::where('session_id', $session->id)->where('is_current', true)->first() : null)
                ?? AcademicCacheService::getCurrentSemester();

        // Fetch student registered course registrations with course details
        $registrations = CourseRegistration::with('course')
            ->where('student_id', $student->id)
            ->when($session, fn ($q) => $q->where('session_id', $session->id))
            ->when($semester, fn ($q) => $q->where('semester_id', $semester->id))
            ->get();

        $registeredCourseIds = $registrations->pluck('course_id');

        // Fetch exam schedules with attendance logs for this candidate
        $schedules = ExamSchedule::with(['course', 'department', 'attendances' => function ($q) use ($student) {
            $q->where('student_id', $student->id);
        }])
            ->whereIn('course_id', $registeredCourseIds)
            ->when($session, fn ($q) => $q->where('session_id', $session->id))
            ->when($semester, fn ($q) => $q->where('semester_id', $semester->id))
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // Fee clearance check
        $isSecondSem = $semester && (stripos($semester->name, 'second') !== false || $semester->name == '2');

        // Auto-cancel 0-paid expired pending invoices (school fees never expire or auto-cancel)
        Invoice::where('user_id', $student->user_id)
            ->where('status', 'pending')
            ->where('paid_amount', '<=', 0)
            ->where('due_date', '<', now())
            ->where('type', '!=', 'school_fee')
            ->update(['status' => 'cancelled']);

        if ($isSecondSem) {
            $pendingInvoices = Invoice::where('user_id', $student->user_id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->count();

            $hasSchoolFeePaid = Invoice::where('user_id', $student->user_id)
                ->where('type', 'school_fee')
                ->when($session, fn ($q) => $q->where('session_id', $session->id))
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
                ->when($session, fn ($q) => $q->where('session_id', $session->id))
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
            'registered_courses' => $registrations->map(fn ($r) => [
                'id' => $r->course_id,
                'code' => $r->course?->code,
                'title' => $r->course?->title,
                'units' => $r->course?->units,
            ])->values()->toArray(),
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
