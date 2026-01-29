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

        // --- NEW: Check Semester Date Window ---
        // Need to know target semester first? Or check active one?
        // Logic: create() shows the form. If we haven't selected a semester yet, we probably default to active.
        // Let's resolve target semester earlier to check dates.

        // ... (Move Semester Resolution Up) ...
        // We will move the logic up to here.

        // Get All Semesters for Session (for dropdown)
        $allSemesters = Semester::where('session_id', $currentSession->id)->get();
        $currentActiveSemester = $allSemesters->where('is_current', true)->first();

        $semesterId = $request->input('semester_id');

        $targetSemester = null;

        if ($semesterId) {
            $targetSemester = $allSemesters->where('id', $semesterId)->first();
        }
        if (!$targetSemester) {
            $targetSemester = $currentActiveSemester;
        }
        if (!$targetSemester && $allSemesters->isNotEmpty()) {
            $targetSemester = $allSemesters->first();
        }

        if ($targetSemester) {
            $now = now();
            $start = $targetSemester->registration_starts_at;
            $end = $targetSemester->registration_ends_at;

            if ($start && $now->lt($start)) {
                return back()->with('error', "Registration for {$targetSemester->name} opens on " . $start->format('d M Y') . ".");
            }
            if ($end && $now->gt($end)) {
                return back()->with('error', "Registration for {$targetSemester->name} closed on " . $end->format('d M Y') . ".");
            }
        }


        // Fee Enforcement: Check if School Fee is paid for current session
        // Logic: Paid 'school_fee' invoice with matching session_id
        $hasPaid = \App\Models\Invoice::where('user_id', Auth::id())
            ->where('type', 'school_fee')
            ->where('status', 'paid')
            ->where('session_id', $currentSession->id)
            ->exists();

        if (!$hasPaid) {
            return redirect()->route('student.payments.index')
                ->with('error', 'You must pay the School Fees for the current session before registering courses.');
        }

        // (Target Semester resolved above for Date Check)

        if (!$targetSemester) {
            return redirect()->route('dashboard')->with('error', 'No semester found for current session.');
        }

        if (!$targetSemester) {
            return redirect()->route('dashboard')->with('error', 'No semester found for current session.');
        }

        // Locking Logic: 
        // Lock ONLY if we are trying to access a Past semester.
        // If target is current -> Open.
        // If target != current:
        //    If current is 'Second' and target is 'First' -> Locked (Past)
        //    Else -> Open (Future or same)
        $isLocked = false;

        if ($currentActiveSemester && $currentActiveSemester->id !== $targetSemester->id) {
            $isSecondActive = stripos($currentActiveSemester->name, 'Second') !== false;
            $isTargetFirst = stripos($targetSemester->name, 'First') !== false;

            if ($isSecondActive && $isTargetFirst) {
                $isLocked = true;
            }
        }

        $department = $student->academicDepartment;

        if (!$department) {
            if (!empty($student->department)) {
                $department = \App\Models\Department::where('name', $student->department)->first();
            }
        }

        if (!$department) {
            return back()->with('error', 'No department assigned to your student profile.');
        }

        // Fetch Student Programme for Max Units and Overrides
        $programme = null;
        if (!empty($student->program)) {
            $programme = \App\Models\Programme::where('name', $student->program)->first();
        }

        $maxUnits = $programme ? $programme->max_credit_units : 24;

        // Dropdown Data
        $faculties = \App\Models\Faculty::select('id', 'name')->orderBy('name')->get();
        $departments = \App\Models\Department::select('id', 'name', 'faculty_id')->orderBy('name')->get();

        // Filter Logic
        $query = Course::query();

        // 1. Filter by Level
        $level = $request->input('level', $student->current_level);
        if ($level) {
            $query->where('level', $level);
        }

        // 2. Filter by Semester (Strictly the target semester)
        // Map Semester Name/ID to the 'semester' column in courses table (1 or 2)
        $semesterCode = (stripos($targetSemester->name, 'Second') !== false || $targetSemester->name === '2') ? '2' : '1';
        $query->where('semester', $semesterCode);


        // 3. Filter by Faculty/Department 
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        } elseif ($request->filled('faculty_id')) {
            $deptIds = \App\Models\Department::where('faculty_id', $request->faculty_id)->pluck('id');
            $query->whereIn('department_id', $deptIds);
        } else {
            // Default to Student's Dept
            if ($department) {
                $query->where('department_id', $department->id);
            }
        }

        $availableCourses = $query->with('department')->orderBy('code')->get();

        // Apply Programme Logic (Overrides)
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

        $registeredCourses = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->where('semester_id', $targetSemester->id) // Scope to Target Semester
            ->with('course')
            ->get()
            ->pluck('course');

        $registeredCourseIds = $registeredCourses->pluck('id');

        return Inertia::render('Student/Courses/Form', [
            'student' => $student,
            'session' => $currentSession,
            'semester' => $targetSemester,
            'semesters' => $allSemesters,
            'isLocked' => $isLocked,
            'faculties' => $faculties,
            'departments' => $departments,
            'courses' => $availableCourses,
            'registeredCourses' => $registeredCourses,
            'registeredCourseIds' => $registeredCourseIds,
            'maxUnits' => $maxUnits,
            'filters' => [
                'level' => $level,
                'semester' => $semesterCode, // This is for the filter dropdown, but we might want to hide/lock it now
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
            'semester_id' => 'required|exists:semesters,id',
        ]);

        $student = Student::where('user_id', Auth::id())->firstOrFail();
        $currentSession = Session::current();
        if (!$currentSession) {
            abort(404, 'No active session.');
        }

        $targetSemester = Semester::where('session_id', $currentSession->id)
            ->where('id', $request->semester_id)
            ->firstOrFail();

        // Enforce Locking
        if (!$targetSemester->is_current) {
            // Check if it's past (same logic as create)
            $currentActiveSemester = Semester::where('session_id', $currentSession->id)->where('is_current', true)->first();

            if ($currentActiveSemester) {
                $isSecondActive = stripos($currentActiveSemester->name, 'Second') !== false;
                $isTargetFirst = stripos($targetSemester->name, 'First') !== false;

                if ($isSecondActive && $isTargetFirst) {
                    return back()->with('error', 'Registration for this semester is closed.');
                }
            }
            // If not past (locked), we allow it (future).
        }

        // Check Max Units logic
        $programme = null;
        if (!empty($student->program)) {
            $programme = \App\Models\Programme::where('name', $student->program)->first();
        }
        $maxUnits = $programme ? $programme->max_credit_units : 24;

        $selectedCourses = Course::whereIn('id', $request->courses)->get();
        // Validation for Units cap
        if ($selectedCourses->sum('units') > $maxUnits) {
            return back()->with('error', "Maximum of {$maxUnits} units allowed.");
        }

        // DB Transaction for safety
        \Illuminate\Support\Facades\DB::transaction(function () use ($student, $currentSession, $targetSemester, $selectedCourses) {

            // Sync Only Target Semester
            CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->where('semester_id', $targetSemester->id)
                ->delete();

            foreach ($selectedCourses as $course) {
                CourseRegistration::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'session_id' => $currentSession->id,
                    'semester_id' => $targetSemester->id,
                ]);
            }
        });

        return to_route('student.courses.index')->with('success', 'Course registration updated successfully.');
    }

    public function downloadForm()
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['user', 'academicDepartment']) // Updated relationship
            ->firstOrFail();

        $currentSession = Session::where('is_current', true)->firstOrFail();
        // Allow downloading form for WHOLE session? or current semester?
        // Let's stick to current logic or update to session.
        // User asked for "both semester at a time". Form should probably show all.

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->with('course', 'semester')
            ->get()
            ->sortBy(function ($reg) {
                return $reg->semester->name . $reg->course->code;
            });

        if ($registrations->isEmpty()) {
            return back()->with('error', 'No course registration found for this session.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.course_form', [
            'student' => $student,
            'registrations' => $registrations,
            'session' => $currentSession,
            'semester' => null, // Generic form for session
            'total_units' => $registrations->sum('course.units'),
        ]);

        return $pdf->download("Course_Form_{$student->matriculation_number}.pdf");
    }
}
