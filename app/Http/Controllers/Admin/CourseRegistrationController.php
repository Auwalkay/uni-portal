<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\AcademicCacheService;
use Inertia\Inertia;

class CourseRegistrationController extends Controller
{
    /**
     * Global search page for course registration
     */
    public function index(Request $request)
    {
        Gate::authorize('manage_student_registrations');

        $query = Student::query()
            ->with(['user', 'academicDepartment.faculty', 'program']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('faculty_id')) {
            $query->whereHas('academicDepartment', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $students = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Courses/RegistrationManager', [
            'students' => $students,
            'filters' => $request->only(['search', 'faculty_id', 'department_id']),
            'faculties' => AcademicCacheService::getFaculties(),
            'departments' => AcademicCacheService::getAllDepartments(),
        ]);
    }

    /**
     * Manage registration for a specific student
     */
    public function manage(Student $student)
    {
        Gate::authorize('manage_student_registrations');

        $currentSession = Session::current();
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // 1. Verify Payment
        $hasPaid = Invoice::where('user_id', $student->user_id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        if (!$hasPaid) {
            return back()->with('error', 'Course registration is only allowed for students who have paid their school fees for the current session.');
        }

        // 2. Fetch Data (from cache)
        $semesters = $currentSession->semesters()->orderBy('name')->get();
        
        $faculties = AcademicCacheService::getFaculties();
        $departments = AcademicCacheService::getAllDepartments();
        $programmes = AcademicCacheService::getProgrammes();

        $level = request('level', $student->current_level);
        $query = Course::where('level', $level);

        if (request('department_id')) {
            $query->where('department_id', request('department_id'));
        } elseif (request('faculty_id')) {
            $deptIds = \App\Models\Department::where('faculty_id', request('faculty_id'))->pluck('id');
            $query->whereIn('department_id', $deptIds);
        } else {
            $query->where('department_id', $student->department_id);
        }

        $availableCourses = $query->with(['department', 'allocations' => function ($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id)->with('staff.user');
            }])
            ->orderBy('semester')
            ->orderBy('code')
            ->get();

        $registeredCourses = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->with('course')
            ->get()
            ->pluck('course');

        $registeredCourseIds = $registeredCourses->pluck('id');

        return Inertia::render('Admin/Students/CourseRegistration', [
            'student' => $student->load('user', 'academicDepartment'),
            'session' => $currentSession,
            'semesters' => $semesters,
            'faculties' => $faculties,
            'departments' => $departments,
            'programmes' => $programmes,
            'courses' => $availableCourses,
            'registeredCourses' => $registeredCourses,
            'registeredCourseIds' => $registeredCourseIds,
            'maxUnits' => 24, // Default or fetch from program
            'filters' => request()->only(['faculty_id', 'department_id', 'level']),
        ]);
    }

    public function store(Request $request, Student $student)
    {
        Gate::authorize('manage_student_registrations');

        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
        ]);

        $currentSession = Session::current();
        if (!$currentSession) {
            abort(404, 'No active session.');
        }

        // Re-verify Payment
        $hasPaid = Invoice::where('user_id', $student->user_id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        if (!$hasPaid) {
            return back()->with('error', 'Student must pay school fees before course registration can be processed.');
        }

        $semesters = Semester::where('session_id', $currentSession->id)->get();
        $firstSemester = $semesters->filter(fn($s) => stripos($s->name, 'First') !== false || $s->name == '1')->first();
        $secondSemester = $semesters->filter(fn($s) => stripos($s->name, 'Second') !== false || $s->name == '2')->first();

        $selectedCourses = Course::whereIn('id', $request->courses)->get();

        DB::transaction(function () use ($student, $currentSession, $firstSemester, $secondSemester, $selectedCourses) {
            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $currentSession->id],
                ['level' => $student->current_level, 'status' => 'active']
            );

            CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->delete();

            foreach ($selectedCourses as $course) {
                $semesterId = $course->semester == '1' ? $firstSemester?->id : $secondSemester?->id;
                
                CourseRegistration::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'session_id' => $currentSession->id,
                    'semester_id' => $semesterId,
                    'student_session_id' => $studentSession->id,
                ]);
            }
        });

        \App\Services\AcademicCacheService::clearTimetableCache();

        return to_route('admin.course_registration.manage', $student->id)->with('success', 'Course registration processed successfully.');
    }

    public function downloadForm(Student $student)
    {
        Gate::authorize('manage_student_registrations');

        $currentSession = Session::current();
        if (!$currentSession) {
            abort(404, 'No active session.');
        }

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->with('course', 'semester')
            ->get()
            ->groupBy(fn($reg) => $reg->semester?->name ?? 'Unknown');

        if ($registrations->isEmpty()) {
            return back()->with('error', 'No course registration records found for this session.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.course_form', [
            'student' => $student->load(['user', 'academicDepartment.faculty', 'program']),
            'registrations' => $registrations,
            'session' => $currentSession,
            'semester' => null,
            'total_units' => $registrations->flatten()->sum('course.units'),
        ]);

        return $pdf->stream("Course_Form_{$student->matriculation_number}.pdf");
    }
}
