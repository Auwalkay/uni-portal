<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\ExamSchedule;
use App\Models\Session;
use App\Models\Semester;
use App\Models\Student;
use App\Services\AcademicCacheService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimetableController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)
            ->whereNotNull('current_level')
            ->whereNotNull('department_id')
            ->with(['department'])
            ->first();

        // If no student profile or level/dept not set, can't show timetable
        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Please update your profile with department and level to view timetable.');
        }

        $currentSession = AcademicCacheService::getCurrentSession();
        $currentSemester = $student->current_semester_id ? Semester::find($student->current_semester_id) : AcademicCacheService::getCurrentSemester();

        $isExamPublished = filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN);

        $timetables = [];
        $examSchedules = [];

        if ($currentSession && $currentSemester) {
            // 1. Weekly Class Timetable (Always accessible to active students)
            $timetables = AcademicCacheService::getStudentTimetable($student->id, $currentSession->id, $currentSemester->id);

            // 2. Exam Timetable for Registered Courses (Only if published by admin)
            if ($isExamPublished) {
                $registeredCourseIds = CourseRegistration::where('student_id', $student->id)
                    ->where('session_id', $currentSession->id)
                    ->pluck('course_id');

                $examSchedules = ExamSchedule::with(['course', 'department', 'session', 'semester'])
                    ->whereIn('course_id', $registeredCourseIds)
                    ->where('session_id', $currentSession->id)
                    ->orderBy('exam_date', 'asc')
                    ->orderBy('start_time', 'asc')
                    ->get();
            }
        }

        return Inertia::render('Student/Timetable/Index', [
            'student' => $student,
            'timetables' => $timetables,
            'examSchedules' => $examSchedules,
            'isExamPublished' => $isExamPublished,
            'session' => $currentSession,
            'semester' => $currentSemester,
        ]);
    }
}
