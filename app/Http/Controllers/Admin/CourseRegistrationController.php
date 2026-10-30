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
use App\Services\AcademicCacheService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseRegistrationController extends Controller
{
    /**
     * Global search page for course registration
     */
    public function index(Request $request)
    {
        Gate::authorize('manage_student_registrations');

        $currentSession = Session::current();
        $currentSemester = Semester::current();

        $sessionId = $request->query('session_id') ?? ($currentSession?->id ?? '');
        $semesterId = $request->query('semester_id');
        $status = $request->query('status', 'all'); // 'all', 'registered', 'not_registered'
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        // Active Session Macro Statistics (Cached for 60s, uses covering index idx_cr_session_student)
        $activeSessionId = $currentSession?->id;
        $overviewStats = Cache::remember("admin_crs_reg_stats_{$activeSessionId}", 60, function () use ($activeSessionId, $currentSession) {
            if (!$activeSessionId) {
                return [
                    'active_session_name' => 'N/A',
                    'total_students' => 0,
                    'total_registered' => 0,
                    'total_pending' => 0,
                    'registration_percentage' => 0,
                    'total_courses_registered' => 0,
                ];
            }

            $totalStudents = Student::where('status', 'active')->count();
            $totalRegistered = CourseRegistration::where('session_id', $activeSessionId)
                ->whereHas('course', fn ($cq) => $cq->where('is_active', true))
                ->distinct('student_id')
                ->count('student_id');
            $totalPending = max(0, $totalStudents - $totalRegistered);
            $percentage = $totalStudents > 0 ? round(($totalRegistered / $totalStudents) * 100, 1) : 0;
            $totalCoursesRegistered = CourseRegistration::where('session_id', $activeSessionId)
                ->whereHas('course', fn ($cq) => $cq->where('is_active', true))
                ->count();

            return [
                'active_session_name' => $currentSession?->name ?? 'Current Session',
                'total_students' => $totalStudents,
                'total_registered' => $totalRegistered,
                'total_pending' => $totalPending,
                'registration_percentage' => $percentage,
                'total_courses_registered' => $totalCoursesRegistered,
            ];
        });

        // Build base student query with filters
        $query = $this->buildStudentsQuery($request, $sessionId, $semesterId, $status);

        // Calculate cohort stats when specific academic filters are applied
        $isFiltered = $request->filled('faculty_id') && $request->faculty_id !== 'all'
            || $request->filled('department_id') && $request->department_id !== 'all'
            || $request->filled('programme_id') && $request->programme_id !== 'all'
            || $request->filled('level') && $request->level !== 'all'
            || $request->filled('search');

        $cohortStats = null;
        if ($isFiltered && $sessionId) {
            $baseCohortQuery = $this->buildStudentsQuery($request, $sessionId, $semesterId, 'all');
            $cohortTotal = (clone $baseCohortQuery)->count();
            $cohortRegistered = (clone $baseCohortQuery)->whereHas('registrations', function ($q) use ($sessionId, $semesterId) {
                $q->where('session_id', $sessionId);
                if ($semesterId && $semesterId !== 'all') {
                    $q->where('semester_id', $semesterId);
                }
                $q->whereHas('course', fn ($cq) => $cq->where('is_active', true));
            })->count();

            $cohortStats = [
                'total' => $cohortTotal,
                'registered' => $cohortRegistered,
                'pending' => max(0, $cohortTotal - $cohortRegistered),
                'percentage' => $cohortTotal > 0 ? round(($cohortRegistered / $cohortTotal) * 100, 1) : 0,
            ];
        }

        // Paginate with restricted columns and eager-load scoped registrations for selected session
        $students = $query->select([
            'students.id',
            'students.user_id',
            'students.matriculation_number',
            'students.department_id',
            'students.faculty_id',
            'students.program_id',
            'students.current_level',
            'students.status',
            'students.passport_photo_path',
            'students.created_at',
        ])
        ->with([
            'user:id,name,email',
            'academicDepartment:id,name,code,faculty_id',
            'academicDepartment.faculty:id,name',
            'program:id,name',
            'registrations' => function ($q) use ($sessionId, $semesterId) {
                $q->where('session_id', $sessionId);
                if ($semesterId && $semesterId !== 'all') {
                    $q->where('semester_id', $semesterId);
                }
                $q->whereHas('course', fn ($cq) => $cq->where('is_active', true));
                $q->with(['course' => fn ($cq) => $cq->where('is_active', true)->select('id', 'code', 'title', 'units', 'is_active')]);
            }
        ])
        ->latest('students.created_at')
        ->paginate($perPage)
        ->withQueryString();

        // Transform results for enriched table cards (only active courses)
        $students->through(function ($student) {
            $regs = ($student->registrations ?? collect())->filter(fn ($r) => $r->course !== null);
            $registeredCoursesCount = $regs->count();
            $registeredUnits = (int) $regs->sum(fn ($r) => $r->course?->units ?? 0);
            $isRegistered = $registeredCoursesCount > 0;
            $courseList = $regs->map(fn ($r) => [
                'id' => $r->course?->id,
                'code' => $r->course?->code ?? 'N/A',
                'title' => $r->course?->title ?? 'N/A',
                'units' => $r->course?->units ?? 0,
            ])->values()->all();

            return [
                'id' => $student->id,
                'user' => [
                    'id' => $student->user?->id,
                    'name' => $student->user?->name ?? 'Unknown Student',
                    'email' => $student->user?->email,
                ],
                'passport_photo_path' => $student->passport_photo_path,
                'matriculation_number' => $student->matriculation_number ?? 'Pending',
                'current_level' => $student->current_level,
                'status' => $student->status,
                'academic_department' => $student->academicDepartment ? [
                    'id' => $student->academicDepartment->id,
                    'name' => $student->academicDepartment->name,
                    'faculty' => $student->academicDepartment->faculty ? [
                        'id' => $student->academicDepartment->faculty->id,
                        'name' => $student->academicDepartment->faculty->name,
                    ] : null,
                ] : null,
                'program' => $student->program ? [
                    'id' => $student->program->id,
                    'name' => $student->program->name,
                ] : null,
                'is_registered' => $isRegistered,
                'registered_courses_count' => $registeredCoursesCount,
                'registered_units' => $registeredUnits,
                'registered_courses' => $courseList,
            ];
        });

        // Get semesters for the selected session
        $semesters = $sessionId ? Semester::where('session_id', $sessionId)->orderBy('name')->get() : collect();

        return Inertia::render('Admin/Courses/RegistrationManager', [
            'students' => $students,
            'overview_stats' => $overviewStats,
            'cohort_stats' => $cohortStats,
            'filters' => [
                'search' => $request->query('search', ''),
                'faculty_id' => $request->query('faculty_id', 'all'),
                'department_id' => $request->query('department_id', 'all'),
                'programme_id' => $request->query('programme_id', 'all'),
                'level' => $request->query('level', 'all'),
                'session_id' => $sessionId,
                'semester_id' => $semesterId ?? '',
                'status' => $status,
                'per_page' => $perPage,
            ],
            'faculties' => AcademicCacheService::getFaculties(true),
            'departments' => AcademicCacheService::getAcademicDepartments(true),
            'programmes' => AcademicCacheService::getProgrammes(true),
            'sessions' => AcademicCacheService::getSessions(),
            'semesters' => $semesters,
            'active_session' => $currentSession ? [
                'id' => $currentSession->id,
                'name' => $currentSession->name,
            ] : null,
        ]);
    }

    /**
     * Stream filtered course registration roster as CSV
     */
    public function exportReport(Request $request)
    {
        Gate::authorize('manage_student_registrations');

        $currentSession = Session::current();
        $sessionId = $request->query('session_id') ?? ($currentSession?->id ?? '');
        $semesterId = $request->query('semester_id');
        $status = $request->query('status', 'all');

        $query = $this->buildStudentsQuery($request, $sessionId, $semesterId, $status);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="course_registrations_' . now()->format('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query, $sessionId, $semesterId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Matriculation Number',
                'Student Name',
                'Email',
                'Faculty',
                'Department',
                'Programme',
                'Level',
                'Student Status',
                'Registration Status',
                'Courses Registered Count',
                'Total Units Registered',
            ]);

            $query->select([
                'students.id',
                'students.user_id',
                'students.matriculation_number',
                'students.department_id',
                'students.faculty_id',
                'students.program_id',
                'students.current_level',
                'students.status',
            ])
            ->with([
                'user:id,name,email',
                'academicDepartment.faculty:id,name',
                'program:id,name',
                'registrations' => function ($q) use ($sessionId, $semesterId) {
                    $q->where('session_id', $sessionId);
                    if ($semesterId && $semesterId !== 'all') {
                        $q->where('semester_id', $semesterId);
                    }
                    $q->whereHas('course', fn ($cq) => $cq->where('is_active', true));
                    $q->with(['course' => fn ($cq) => $cq->where('is_active', true)->select('id', 'units', 'is_active')]);
                }
            ])
            ->chunk(500, function ($students) use ($handle) {
                foreach ($students as $student) {
                    $regs = ($student->registrations ?? collect())->filter(fn ($r) => $r->course !== null);
                    $coursesCount = $regs->count();
                    $unitsCount = $regs->sum(fn ($r) => $r->course?->units ?? 0);
                    $regStatus = $coursesCount > 0 ? 'Registered' : 'Not Registered';

                    fputcsv($handle, [
                        $student->matriculation_number ?? 'Pending',
                        $student->user?->name ?? 'N/A',
                        $student->user?->email ?? 'N/A',
                        $student->academicDepartment?->faculty?->name ?? 'N/A',
                        $student->academicDepartment?->name ?? 'N/A',
                        $student->program?->name ?? 'N/A',
                        $student->current_level ? $student->current_level . ' Level' : 'N/A',
                        ucfirst($student->status ?? 'active'),
                        $regStatus,
                        $coursesCount,
                        $unitsCount,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Build reusable filtered student query
     */
    private function buildStudentsQuery(Request $request, string $sessionId, ?string $semesterId, string $status = 'all')
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('faculty_id') && $request->faculty_id !== 'all') {
            $facultyId = $request->faculty_id;
            $query->where(function ($q) use ($facultyId) {
                $q->where('faculty_id', $facultyId)
                  ->orWhereHas('academicDepartment', fn ($dq) => $dq->where('faculty_id', $facultyId));
            });
        }

        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('programme_id') && $request->programme_id !== 'all') {
            $query->where('program_id', $request->programme_id);
        }

        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('current_level', $request->level);
        }

        // Apply Registration Status Filter using index idx_cr_session_student / idx_cr_session_sem_student
        if ($sessionId && $status === 'registered') {
            $query->whereHas('registrations', function ($q) use ($sessionId, $semesterId) {
                $q->where('session_id', $sessionId);
                if ($semesterId && $semesterId !== 'all') {
                    $q->where('semester_id', $semesterId);
                }
                $q->whereHas('course', fn ($cq) => $cq->where('is_active', true));
            });
        } elseif ($sessionId && $status === 'not_registered') {
            $query->whereDoesntHave('registrations', function ($q) use ($sessionId, $semesterId) {
                $q->where('session_id', $sessionId);
                if ($semesterId && $semesterId !== 'all') {
                    $q->where('semester_id', $semesterId);
                }
                $q->whereHas('course', fn ($cq) => $cq->where('is_active', true));
            });
        }

        return $query;
    }

    /**
     * Manage registration for a specific student
     */
    public function manage(Student $student)
    {
        Gate::authorize('manage_student_registrations');

        $currentSession = Session::current();
        if (! $currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // 1. Verify Payment
        $hasPaid = Invoice::where('user_id', $student->user_id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        if (! $hasPaid) {
            return back()->with('error', 'Course registration is only allowed for students who have paid their school fees for the current session.');
        }

        // 2. Fetch Data (from cache)
        $semesters = $currentSession->semesters()->orderBy('name')->get();

        $faculties = AcademicCacheService::getFaculties(true);
        $departments = AcademicCacheService::getAcademicDepartments(true);
        $programmes = AcademicCacheService::getProgrammes(true);

        $level = request('level', $student->current_level);
        $query = Course::where('level', $level)->where('is_active', true);
        $query->whereHas('department', fn($d) => $d->where('is_active', true));

        if (request()->filled('programme_id') && request('programme_id') !== 'all') {
            $programmeId = request('programme_id');
            $query->where(function ($q) use ($programmeId) {
                $q->where('programme_id', $programmeId)
                  ->orWhereExists(function ($sub) use ($programmeId) {
                      $sub->select(DB::raw(1))
                          ->from('course_programme')
                          ->whereColumn('course_programme.course_id', 'courses.id')
                          ->where('course_programme.programme_id', $programmeId);
                  });
            });
        }

        if (request()->filled('department_id') && request('department_id') !== 'all') {
            $query->where('department_id', request('department_id'));
        } elseif (request()->filled('faculty_id') && request('faculty_id') !== 'all') {
            $deptIds = \App\Models\Department::where('faculty_id', request('faculty_id'))
                ->where('is_active', true)
                ->pluck('id');
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
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->with(['course' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->pluck('course')
            ->filter();

        $registeredCourseIds = $registeredCourses->pluck('id')->values()->all();

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
            'filters' => request()->only(['faculty_id', 'department_id', 'programme_id', 'level']),
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
        if (! $currentSession) {
            abort(404, 'No active session.');
        }

        // Re-verify Payment
        $hasPaid = Invoice::where('user_id', $student->user_id)
            ->where('type', 'school_fee')
            ->whereIn('status', ['paid', 'partial'])
            ->where('session_id', $currentSession->id)
            ->exists();

        if (! $hasPaid) {
            return back()->with('error', 'Student must pay school fees before course registration can be processed.');
        }

        $semesters = Semester::where('session_id', $currentSession->id)->get();
        $firstSemester = $semesters->filter(fn ($s) => stripos($s->name, 'First') !== false || $s->name == '1')->first();
        $secondSemester = $semesters->filter(fn ($s) => stripos($s->name, 'Second') !== false || $s->name == '2')->first();

        $selectedCourses = Course::whereIn('id', $request->courses)->where('is_active', true)->get();
        if ($selectedCourses->count() !== count($request->courses)) {
            return back()->with('error', 'One or more selected courses are inactive.');
        }

        DB::transaction(function () use ($student, $currentSession, $firstSemester, $secondSemester, $selectedCourses) {
            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $currentSession->id],
                ['level' => $student->current_level, 'status' => 'active']
            );

            CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->delete();

            foreach ($selectedCourses as $course) {
                $semesterId = ($course->semester == '1' || $course->semester == 1)
                    ? $firstSemester?->id
                    : ($secondSemester?->id ?? $firstSemester?->id);

                CourseRegistration::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'session_id' => $currentSession->id,
                    'semester_id' => $semesterId,
                    'student_session_id' => $studentSession->id,
                ]);
            }
        });

        AcademicCacheService::clearTimetableCache();
        Cache::forget("admin_crs_reg_stats_{$currentSession->id}");

        return to_route('admin.course_registration.manage', $student->id)->with('success', 'Course registration processed successfully.');
    }

    public function downloadForm(Request $request, Student $student)
    {
        Gate::authorize('manage_student_registrations');

        $currentSession = Session::current();
        if (! $currentSession) {
            abort(404, 'No active session.');
        }

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->with(['course' => fn ($q) => $q->where('is_active', true), 'semester'])
            ->get()
            ->groupBy(fn ($reg) => $reg->semester?->name ?? 'Unknown');

        if ($registrations->isEmpty()) {
            return back()->with('error', 'No course registration records found for this session.');
        }

        if ($request->query('download') === '1') {
            $pdf = Pdf::loadView('documents.course_form', [
                'student' => $student->load(['user', 'academicDepartment.faculty', 'program']),
                'registrations' => $registrations,
                'session' => $currentSession,
                'semester' => null,
                'total_units' => $registrations->flatten()->sum('course.units'),
            ]);

            return $pdf->download("Course_Form_{$student->matriculation_number}.pdf");
        }

        return Inertia::render('Admin/Students/CourseRegistrationFormView', [
            'student' => $student->load(['user', 'academicDepartment.faculty', 'program']),
            'registrations' => $registrations,
            'session' => $currentSession,
            'total_units' => $registrations->flatten()->sum('course.units'),
        ]);
    }
}
