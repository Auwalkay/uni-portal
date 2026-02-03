<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Student;
use App\Models\Timetable;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TimetableController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', Auth::id())
            ->whereNotNull('current_level')
            ->whereNotNull('department_id')
            ->with(['department'])
            ->first();

        // If no student profile or level/dept not set, can't show timetable
        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Please update your profile with department and level to view timetable.');
        }

        $currentSession = Session::current();
        // Assuming there's a current semester helper or logic
        $currentSemester = \App\Models\Semester::where('session_id', $currentSession->id)->where('is_current', true)->first();

        $timetables = [];
        if ($currentSession && $currentSemester) {
            // Get user's registered courses for this semester
            $registeredCourseIds = \App\Models\CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->where('semester_id', $currentSemester->id)
                ->pluck('course_id');

            $timetables = Timetable::where('session_id', $currentSession->id)
                ->where('semester_id', $currentSemester->id)
                // Filter by registered courses ONLY
                ->whereIn('course_id', $registeredCourseIds)
                ->with([
                    'course',
                    'course.allocations' => function ($q) use ($currentSession) {
                        $q->where('session_id', $currentSession->id)->with('staff.user');
                    }
                ])
                ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')")
                ->orderBy('start_time')
                ->get();
        }

        return Inertia::render('Student/Timetable/Index', [
            'student' => $student,
            'timetables' => $timetables,
            'session' => $currentSession,
            'semester' => $currentSemester,
        ]);
    }
}
