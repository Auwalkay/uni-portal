<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\ExamSchedule;
use App\Models\Invoice;
use App\Services\AcademicCacheService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamDocketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Student profile not found.');
        }

        $currentSession = AcademicCacheService::getCurrentSession();
        $currentSemester = $student->current_semester_id ? \App\Models\Semester::find($student->current_semester_id) : AcademicCacheService::getCurrentSemester();

        $isExamPublished = filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN);

        // 1. Registered Courses for Student (Current Semester Only)
        $registrations = CourseRegistration::where('student_id', $student->id)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->when($currentSemester, fn ($q) => $q->where('semester_id', $currentSemester->id))
            ->with(['course', 'semester'])
            ->get();

        $registeredCourseIds = $registrations->pluck('course_id');

        // 2. Exam Schedules for Registered Courses (Current Semester Only, Keyed by course_id)
        $examSchedules = $isExamPublished ? ExamSchedule::whereIn('course_id', $registeredCourseIds)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->when($currentSemester, fn ($q) => $q->where('semester_id', $currentSemester->id))
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->keyBy('course_id') : collect([]);

        $schedules = $registrations->map(function ($reg) use ($examSchedules) {
            $course = $reg->course;
            $schedule = $course ? $examSchedules->get($course->id) : null;

            return [
                'id' => $reg->id,
                'course_id' => $course?->id,
                'course' => $course,
                'exam_date' => $schedule?->exam_date ? $schedule->exam_date->format('Y-m-d') : null,
                'start_time' => $schedule?->start_time,
                'end_time' => $schedule?->end_time,
                'venue' => $schedule?->venue ?? 'TBA',
                'exam_type' => $schedule?->exam_type ?? 'FINAL',
            ];
        })->values();

        // 3. Fee Clearance Checks
        $isSecondSem = $currentSemester && (stripos($currentSemester->name, 'second') !== false || $currentSemester->name == '2');

        // Auto-cancel 0-paid expired pending invoices so they don't block clearance
        Invoice::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('paid_amount', '<=', 0)
            ->where('due_date', '<', now())
            ->update(['status' => 'cancelled']);

        if ($isSecondSem) {
            // Second Semester: Require 100% FULL payment ('paid') for all fees.
            // Partial payments trigger the pending fee warning.
            $pendingInvoicesCount = Invoice::where('user_id', $user->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->count();

            $hasSchoolFeePaid = Invoice::where('user_id', $user->id)
                ->where('type', 'school_fee')
                ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
                ->where('status', 'paid')
                ->exists();

            $isFeeCleared = ($pendingInvoicesCount === 0 && $hasSchoolFeePaid);
        } else {
            // First Semester: Invoices with status 'paid' or 'partial' (or paid_amount > 0) are Accepted & CLEARED.
            // Partial payments do NOT trigger the fee warning in First Semester.
            $pendingInvoicesCount = Invoice::where('user_id', $user->id)
                ->whereNotIn('status', ['paid', 'partial', 'cancelled'])
                ->where('paid_amount', '<=', 0)
                ->where(function ($q) {
                    $q->whereNull('due_date')->orWhere('due_date', '>=', now());
                })
                ->count();

            $hasSchoolFeeCleared = Invoice::where('user_id', $user->id)
                ->where('type', 'school_fee')
                ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
                ->whereIn('status', ['paid', 'partial'])
                ->exists();

            $isFeeCleared = ($pendingInvoicesCount === 0 && $hasSchoolFeeCleared);
        }

        $hasRegistrations = ($registeredCourseIds->count() > 0);
        $isCleared = $isFeeCleared && $hasRegistrations;

        // 4. Generate Security Verification Hash / Payload
        $docketToken = strtoupper(md5($student->id . ($currentSession?->id ?? '') . 'EXAM_PERMIT_SECRET'));
        $verificationQrData = json_encode([
            'matric' => $student->matric_number,
            'name' => $user->name,
            'session' => $currentSession?->name ?? 'Current',
            'cleared' => $isCleared,
            'token' => $docketToken,
        ]);

        return Inertia::render('Student/Exams/Docket', [
            'student' => [
                'id' => $student->id,
                'name' => $user->name,
                'email' => $user->email,
                'matric_number' => $student->matric_number ?? 'N/A',
                'department' => $student->department?->name ?? 'N/A',
                'programme' => $student->programme?->name ?? 'N/A',
                'level' => $student->level ?? '100',
                'passport_photo' => $student->passport_photo_path ? asset('storage/' . $student->passport_photo_path) : null,
            ],
            'session' => $currentSession,
            'semester' => $currentSemester,
            'schedules' => $schedules,
            'clearance' => [
                'is_cleared' => $isCleared,
                'fee_cleared' => $isFeeCleared,
                'course_reg_cleared' => $hasRegistrations,
                'registered_courses_count' => $registeredCourseIds->count(),
                'pending_invoices_count' => $pendingInvoicesCount,
            ],
            'verificationQrData' => $verificationQrData,
            'docketToken' => $docketToken,
            'isPublished' => $isExamPublished,
        ]);
    }
}
