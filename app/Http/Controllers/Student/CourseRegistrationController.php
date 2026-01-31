<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Session;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseRegistrationController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();

        // Fetch all registrations
        $registrations = CourseRegistration::where('student_id', $student->id)
            ->with(['session', 'semester', 'course'])
            ->get();

        // Group by Session (ID)
        $groupedBySession = $registrations->groupBy('session_id');

        $formattedHistory = [];

        foreach ($groupedBySession as $sessionId => $sessionRegistrations) {
            $session = $sessionRegistrations->first()->session;

            // Within Session, Group by Semester
            $semesters = $sessionRegistrations->groupBy('semester_id')->map(function ($semesterRegs) {
                $semester = $semesterRegs->first()->semester;
                return [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'is_current' => $semester->is_current,
                    'total_units' => $semesterRegs->sum('course.units'),
                    'courses' => $semesterRegs->map(fn($r) => $r->course),
                ];
            })->values();

            $formattedHistory[] = [
                'id' => $session->id,
                'session' => $session->name,
                'is_current' => $session->is_current,
                'semesters' => $semesters
            ];
        }

        return Inertia::render('Student/Courses/Index', [
            'history' => $formattedHistory,
            'student' => $student
        ]);
    }

    public function create(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->with('academicDepartment')->first();

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'You are not yet a matriculated student.');
        }

        $currentSession = Session::current();
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        if (!$currentSession->registration_enabled) {
            return back()->with('error', 'Course registration is currently closed for this session.');
        }

        // Fee Enforcement
        $hasPaid = \App\Models\Invoice::where('user_id', Auth::id())
            ->where('type', 'school_fee')
            ->where('status', 'paid')
            ->where('session_id', $currentSession->id)
            ->exists();

        if (!$hasPaid) {
            return redirect()->route('student.payments.index')
                ->with('error', 'You must pay the School Fees for the current session before registering courses.');
        }

        // Get Semesters
        $semesters = Semester::where('session_id', $currentSession->id)->orderBy('name')->get();

        $firstSemester = $semesters->filter(fn($s) => stripos($s->name, 'First') !== false || $s->name == '1')->first();
        $secondSemester = $semesters->filter(fn($s) => stripos($s->name, 'Second') !== false || $s->name == '2')->first();

        // Check Locks (Registration Dates)
        $now = now();
        $locks = [
            '1' => false,
            '2' => false
        ];

        if ($firstSemester) {
            if ($firstSemester->registration_starts_at && $now->lt($firstSemester->registration_starts_at))
                $locks['1'] = true;
            if ($firstSemester->registration_ends_at && $now->gt($firstSemester->registration_ends_at))
                $locks['1'] = true;
        }
        if ($secondSemester) {
            if ($secondSemester->registration_starts_at && $now->lt($secondSemester->registration_starts_at))
                $locks['2'] = true;
            if ($secondSemester->registration_ends_at && $now->gt($secondSemester->registration_ends_at))
                $locks['2'] = true;
        }

        $department = $student->academicDepartment;
        if (!$department && !empty($student->department)) {
            $department = \App\Models\Department::where('name', $student->department)->first();
        }

        if (!$department) {
            return back()->with('error', 'No department assigned to your student profile.');
        }

        // Programme Limits
        $programme = null;
        if (!empty($student->program)) {
            $programme = \App\Models\Programme::where('name', $student->program)->first();
        }
        $maxUnits = $programme ? $programme->max_credit_units : 24;

        // Dropdown Data
        $faculties = \App\Models\Faculty::select('id', 'name')->orderBy('name')->get();
        $departments = \App\Models\Department::select('id', 'name', 'faculty_id')->orderBy('name')->get();

        // 1. Fetch ALL Courses for Session (Both Semesters)
        $query = Course::query();

        // Filter by Level
        $level = $request->input('level', $student->current_level);
        if ($level) {
            $query->where('level', $level);
        }

        // Department Logic (Same as before)
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        } elseif ($request->filled('faculty_id')) {
            $deptIds = \App\Models\Department::where('faculty_id', $request->faculty_id)->pluck('id');
            $query->whereIn('department_id', $deptIds);
        } else {
            if ($department) {
                $query->where('department_id', $department->id);
            }
        }

        $availableCourses = $query->with('department')->orderBy('semester')->orderBy('code')->get();

        // Apply Programme Overrides (Compulsory)
        if ($programme) {
            $overrides = \Illuminate\Support\Facades\DB::table('course_programme')
                ->where('programme_id', $programme->id)
                ->pluck('is_compulsory', 'course_id');

            $availableCourses->transform(function ($course) use ($overrides) {
                if ($overrides->has($course->id)) {
                    $course->is_compulsory = (bool) $overrides->get($course->id);
                }
                return $course;
            });
        }

        // Fetch Existing Registrations for Session
        $registeredCourses = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->with('course')
            ->get()
            ->pluck('course');

        $registeredCourseIds = $registeredCourses->pluck('id');

        return Inertia::render('Student/Courses/Form', [
            'student' => $student,
            'session' => $currentSession,
            'semesters' => $semesters,
            'locks' => $locks,
            'faculties' => $faculties,
            'departments' => $departments,
            'courses' => $availableCourses,
            'registeredCourses' => $registeredCourses,
            'registeredCourseIds' => $registeredCourseIds,
            'maxUnits' => $maxUnits,
            'filters' => [
                'level' => $level,
                'faculty_id' => $request->input('faculty_id'),
                'department_id' => $request->input('department_id'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
        ]);

        $student = Student::where('user_id', Auth::id())->firstOrFail();
        $currentSession = Session::current();
        if (!$currentSession) {
            abort(404, 'No active session.');
        }

        // Resolve Semesters
        $semesters = Semester::where('session_id', $currentSession->id)->get();
        $firstSemester = $semesters->filter(fn($s) => stripos($s->name, 'First') !== false || $s->name == '1')->first();
        $secondSemester = $semesters->filter(fn($s) => stripos($s->name, 'Second') !== false || $s->name == '2')->first();

        // Max Units Check (Global or Per Semester? Usually Per Semester, but let's assume Global for simplicity requested, or Per Semester)
        // User request was simple "select and show".
        // Let's implement Per Semester Unit Cap if possible, but for now stick to global maxUnits provided in create.
        $programme = null;
        if (!empty($student->program)) {
            $programme = \App\Models\Programme::where('name', $student->program)->first();
        }
        $maxUnits = $programme ? $programme->max_credit_units : 24;

        // Ensure total units don't exceed max * 2 (if max is per semester) or just max?
        // Usually max is PER SEMESTER.
        // Let's check max units per semester.

        $selectedCourses = Course::whereIn('id', $request->courses)->get();

        $firstSemCourses = $selectedCourses->where('semester', '1');
        $secondSemCourses = $selectedCourses->where('semester', '2');

        if ($firstSemCourses->sum('units') > $maxUnits) {
            return back()->with('error', "Maximum of {$maxUnits} units allowed for First Semester.");
        }
        if ($secondSemCourses->sum('units') > $maxUnits) {
            return back()->with('error', "Maximum of {$maxUnits} units allowed for Second Semester.");
        }

        // DB Transaction
        \Illuminate\Support\Facades\DB::transaction(function () use ($student, $currentSession, $firstSemester, $secondSemester, $firstSemCourses, $secondSemCourses) {

            // Delete ALL registrations for this session to handle updates (unchecking courses)
            // Or better: Delete for First Sem if we are updating First Sem?
            // Since we submit ALL, we can sync ALL.
            CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->delete();

            // Insert First Semester
            if ($firstSemester) {
                foreach ($firstSemCourses as $course) {
                    CourseRegistration::create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'session_id' => $currentSession->id,
                        'semester_id' => $firstSemester->id,
                    ]);
                }
            }

            // Insert Second Semester
            if ($secondSemester) {
                foreach ($secondSemCourses as $course) {
                    CourseRegistration::create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'session_id' => $currentSession->id,
                        'semester_id' => $secondSemester->id,
                    ]);
                }
            }
        });

        return to_route('student.courses.index')->with('success', 'Course registration updated successfully.');
    }

    public function downloadForm(Request $request)
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['user', 'academicDepartment'])
            ->firstOrFail();

        $sessionId = $request->query('session_id');

        if ($sessionId) {
            $session = Session::findOrFail($sessionId);
        } else {
            $session = Session::where('is_current', true)->firstOrFail();
        }

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->with('course', 'semester')
            ->get()
            ->groupBy(fn($reg) => $reg->semester->name);


        if ($registrations->isEmpty()) {
            return response("No course registration records found for this session ({$session->name}). Please ensure you have registered courses.", 404);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.course_form', [
            'student' => $student,
            'registrations' => $registrations,
            'session' => $session,
            'semester' => null, // Generic form for session
            'total_units' => $registrations->sum('course.units'),
        ]);

        // $safeSessionName = str_replace(['/', '\\'], '-', $session->name);
        return $pdf->download("Course_Form.pdf");
    }
}
