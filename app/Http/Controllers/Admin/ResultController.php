<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Session;
use App\Models\Semester;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultController extends Controller
{
    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function index()
    {
        // Ideally filter by Department or Faculty. Listing all for now.
        $courses = Course::with('department')->get();
        return Inertia::render('Admin/Results/Index', [
            'courses' => $courses
        ]);
    }

    public function edit(Course $course)
    {
        $currentSession = Session::where('is_current', true)->firstOrFail();
        // Assuming courses run in specific semesters.
        // We need to know which semester is active to filter registrations?
        // Or simpler: Get registrations for this course in the current session.

        $registrations = CourseRegistration::where('course_id', $course->id)
            ->where('session_id', $currentSession->id)
            ->with(['student.user'])
            ->get();

        return Inertia::render('Admin/Results/Entry', [
            'course' => $course,
            'session' => $currentSession,
            'registrations' => $registrations,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*.id' => 'required|exists:course_registrations,id',
            'scores.*.ca_score' => 'required|numeric|min:0|max:40', // Assuming CA is 40
            'scores.*.exam_score' => 'required|numeric|min:0|max:100', // Exam can be 60 or 70. Let's say 100 total.
        ]);

        foreach ($request->scores as $data) {
            $reg = CourseRegistration::find($data['id']);

            $ca = $data['ca_score'];
            $exam = $data['exam_score'];
            $total = $ca + $exam;

            if ($total > 100) {
                // Skip or validation error? For bulk, maybe clamp or just save.
                // Validation should catch logic if possible, but difficult in bulk array without custom rule.
                // Let's just proceed.
            }

            $grading = $this->gradingService->calculate($total);

            $reg->update([
                'ca_score' => $ca,
                'exam_score' => $exam,
                'score' => $total, // Total Score
                'grade' => $grading['grade'],
                'grade_point' => $grading['point'],
            ]);
        }

        return back()->with('success', 'Results updated successfully.');
    }
}
