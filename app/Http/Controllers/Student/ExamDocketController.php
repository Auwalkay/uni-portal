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

        // 1. Registered Courses
        $registeredCourseIds = CourseRegistration::where('student_id', $student->id)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->pluck('course_id');

        // 2. Exam Schedules for Registered Courses (only if published by admin)
        $schedules = $isExamPublished ? ExamSchedule::with(['course', 'department', 'session', 'semester'])
            ->whereIn('course_id', $registeredCourseIds)
            ->when($currentSession, fn ($q) => $q->where('session_id', $currentSession->id))
            ->orderBy('exam_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get() : collect([]);

        // 3. Fee Clearance Checks
        $pendingInvoicesCount = Invoice::where('user_id', $user->id)
            ->where('status', 'unpaid')
            ->count();
        
        $isFeeCleared = ($pendingInvoicesCount === 0);
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
