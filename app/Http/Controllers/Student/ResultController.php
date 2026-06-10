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
        $student = Student::where('user_id', Auth::id())->with('program')->firstOrFail();

        // 1. Get all unique sessions the student has registered for
        // We can do this by getting distinct session_ids via registrations
        $sessionIds = CourseRegistration::where('student_id', $student->id)
            ->distinct()
            ->pluck('session_id');

        $sessions = Session::whereIn('id', $sessionIds)
            ->orderBy('start_date', 'desc') // Latest session first
            ->get();

        $programme = $student->program;
        $overrides = collect();
        if ($programme) {
            $overrides = \Illuminate\Support\Facades\DB::table('course_programme')
                ->where('programme_id', $programme->id)
                ->pluck('is_compulsory', 'course_id');
        }

        // 2. Build the History Structure
        $history = [];

        foreach ($sessions as $session) {
            $sessionData = [
                'id' => $session->id,
                'name' => $session->name,
                'is_current' => $session->is_current,
                'semesters' => []
            ];

            // Get semesters for this session (that have published registrations)
            $registrationsInSession = CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $session->id)
                ->where('is_published', true)
                ->with(['course', 'semester'])
                ->get()
                ->groupBy('semester.name'); // Group by Semester Name (First/Second)

            // We want to order semesters logically (First then Second)
            // But groupBy returns a collection keyed by name.
            // Let's iterate and calculate GPA

            foreach ($registrationsInSession as $semesterName => $regs) {
                // Attach overrides to courses
                foreach ($regs as $reg) {
                    if ($reg->course) {
                        $reg->course->is_compulsory = $overrides->has($reg->course->id) ? (bool)$overrides->get($reg->course->id) : false;
                    }
                }

                // Calculate GPA
                $gpa = $this->gradingService->calculateGPA($regs);

                $sessionData['semesters'][] = [
                    'name' => $semesterName,
                    'gpa' => $gpa,
                    'courses' => $regs
                ];
            }

            // Sort semesters (First before Second) - Optional but good for display
            usort($sessionData['semesters'], function ($a, $b) {
                return strpos($a['name'], 'Second') !== false ? 1 : -1;
            });

            $history[] = $sessionData;
        }

        // 3. CGPA Calculation (Only published registrations)
        $allRegs = CourseRegistration::where('student_id', $student->id)
            ->where('is_published', true)
            ->with('course')
            ->get();
        $cgpa = $this->gradingService->calculateGPA($allRegs);

        return Inertia::render('Student/Results/Index', [
            'history' => $history,
            'cgpa' => $cgpa
        ]);
    }
}
