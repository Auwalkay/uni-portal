<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Session;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ResultImport;

class ResultController extends Controller
{
    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function index(Request $request)
    {
        $sessions = Session::orderBy('start_date', 'desc')->get();
        $departments = Department::orderBy('name')->get();
        $faculties = Faculty::orderBy('name')->get();

        $currentSession = Session::where('is_current', true)->first();

        $selectedSessionId = $request->input('session_id', $currentSession?->id);
        $selectedDepartmentId = $request->input('department_id');
        $selectedLevel = $request->input('level');
        $hasRegistrations = $request->boolean('has_registrations');

        $courses = Course::query()
            ->with(['department', 'program'])
            ->when($selectedDepartmentId, function ($query, $deptId) {
                $query->where('department_id', $deptId);
            })
            ->when($selectedLevel, function ($query, $level) {
                $query->where('level', $level);
            })
            ->when($hasRegistrations, function ($query) use ($selectedSessionId) {
                $query->whereHas('registrations', function ($q) use ($selectedSessionId) {
                    $q->where('session_id', $selectedSessionId);
                });
            })
            // Optimization: Load results stats for the selected session
            ->withCount([
                'registrations as graded_count' => function ($query) use ($selectedSessionId) {
                    $query->where('session_id', $selectedSessionId)
                        ->whereNotNull('score');
                }
            ])
            ->withCount([
                'registrations as total_students' => function ($query) use ($selectedSessionId) {
                    $query->where('session_id', $selectedSessionId);
                }
            ])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Results/Index', [
            'sessions' => $sessions,
            'departments' => $departments,
            'faculties' => $faculties, // Optional if we want filtering by faculty
            'courses' => $courses,
            'filters' => [
                'session_id' => $selectedSessionId,
                'department_id' => $selectedDepartmentId,
                'level' => $selectedLevel,
                'has_registrations' => $hasRegistrations,
            ]
        ]);
    }

    public function edit(Course $course, Request $request)
    {
        // Allow passing session_id, default to current
        $sessionId = $request->input('session_id');
        if ($sessionId) {
            $session = Session::findOrFail($sessionId);
        } else {
            $session = Session::where('is_current', true)->firstOrFail();
        }

        $registrations = CourseRegistration::where('course_id', $course->id)
            ->where('session_id', $session->id)
            ->with(['student.user'])
            ->orderBy('student_id') // Roughly by registration or matric
            ->get();

        return Inertia::render('Admin/Results/Entry', [
            'course' => $course,
            'session' => $session,
            'registrations' => $registrations,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*.id' => 'required|exists:course_registrations,id',
            'scores.*.ca_score' => 'nullable|numeric|min:0|max:40',
            'scores.*.exam_score' => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($request->scores as $data) {
            $reg = CourseRegistration::find($data['id']);

            $ca = $data['ca_score'] ?? 0;
            $exam = $data['exam_score'] ?? 0;
            $total = $ca + $exam;

            // Simple validation clamp
            if ($total > 100)
                $total = 100;

            $grading = $this->gradingService->calculate($total);

            $reg->update([
                'ca_score' => $ca,
                'exam_score' => $exam,
                'score' => $total,
                'grade' => $grading['grade'],
                'grade_point' => $grading['point'],
            ]);
        }

        return back()->with('success', 'Results updated successfully.');
    }

    public function upload(Request $request, Course $course)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'session_id' => 'required|exists:academic_sessions,id'
        ]);

        $session = Session::findOrFail($request->session_id);

        try {
            Excel::import(new ResultImport($course, $session, $this->gradingService), $request->file('file'));
            return back()->with('success', 'Results imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
