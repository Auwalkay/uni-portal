<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Departments;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CourseRegistrationsExport; // We will need to create this export class

class CourseRegistrationController extends Controller
{
    public function index(Course $course, Request $request)
    {
        $currentSession = Session::where('is_current', true)->first();

        $selectedSessionId = $request->input('session_id');
        $selectedLevel = $request->input('level');
        $selectedDepartmentId = $request->input('department_id');
        $selectedFacultyId = $request->input('faculty_id');
        $selectedProgrammeId = $request->input('programme_id');

        // Initial Logic: If no session selected, maybe default to current or show all?
        // User request: "get and download all the students that registers for a specific course with filters base on session..."
        // So we should allow filtering. Defaulting to all or current seems reasonable. Let's default to current if available.
        // Actually, for broad view, default to null (All) might be safer, but usually we care about current.
        // Let's use current session as default filter if not provided, to keep view clean.

        if (!$request->has('session_id') && $currentSession) {
            $selectedSessionId = $currentSession->id;
        }

        $registrations = CourseRegistration::query()
            ->where('course_id', $course->id)
            ->with(['student.user', 'student.department', 'student.program', 'session'])
            ->when($selectedSessionId, fn($q) => $q->where('session_id', $selectedSessionId))
            ->when($selectedLevel, fn($q) => $q->whereHas('student', fn($s) => $s->where('current_level', $selectedLevel))) // Or should it be level at registration? Usually student's current level or level in registration? Reg doesn't store level. We use student current level or infer. 
            // Note: Registrations don't store student level snapshot. Using student.current_level is slightly inaccurate for past sessions but standard for simple systems.
            ->when($selectedDepartmentId, fn($q) => $q->whereHas('student', fn($s) => $s->where('department_id', $selectedDepartmentId)))
            ->when($selectedFacultyId, fn($q) => $q->whereHas('student.department', fn($d) => $d->where('faculty_id', $selectedFacultyId)))
            ->when($selectedProgrammeId, fn($q) => $q->whereHas('student', fn($s) => $s->where('programme_id', $selectedProgrammeId)))
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Course/Registrations', [
            'course' => $course,
            'registrations' => $registrations,
            'sessions' => Session::orderBy('start_date', 'desc')->get(),
            'faculties' => Faculty::orderBy('name')->get(), // For cascades if needed
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'programmes' => Programme::orderBy('name')->get(),
            'filters' => [
                'session_id' => $selectedSessionId,
                'level' => $selectedLevel,
                'department_id' => $selectedDepartmentId,
                'faculty_id' => $selectedFacultyId,
                'programme_id' => $selectedProgrammeId,
            ]
        ]);
    }

    public function export(Course $course, Request $request)
    {
        // Same filters as index
        $selectedSessionId = $request->input('session_id');
        $selectedLevel = $request->input('level');
        $selectedDepartmentId = $request->input('department_id');
        $selectedFacultyId = $request->input('faculty_id');
        $selectedProgrammeId = $request->input('programme_id');

        return Excel::download(new \App\Exports\CourseRegistrationsExport(
            $course->id,
            $selectedSessionId,
            $selectedLevel,
            $selectedDepartmentId,
            $selectedFacultyId,
            $selectedProgrammeId
        ), 'course_registrations_' . $course->code . '.xlsx');
    }
}
