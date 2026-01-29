<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $student = Student::where('user_id', Auth::id())->firstOrFail();

        // Get all sessions/semesters student has registered for
        // Group by Session, then Semester
        $registrations = CourseRegistration::where('student_id', $student->id)
            ->with(['course', 'session', 'semester'])
            ->get()
            ->groupBy(['session.name', 'semester.name']);

        // Calculate GPA per semester
        $results = [];

        foreach ($registrations as $sessionName => $semesters) {
            foreach ($semesters as $semesterName => $regs) {
                $gpa = $this->gradingService->calculateGPA($regs);
                $results[$sessionName][$semesterName] = [
                    'gpa' => $gpa,
                    'courses' => $regs
                ];
            }
        }

        // CGPA Calculation (All registrations)
        $allRegs = CourseRegistration::where('student_id', $student->id)->with('course')->get();
        $cgpa = $this->gradingService->calculateGPA($allRegs);

        return Inertia::render('Student/Results/Index', [
            'results' => $results,
            'cgpa' => $cgpa
        ]);
    }
}
