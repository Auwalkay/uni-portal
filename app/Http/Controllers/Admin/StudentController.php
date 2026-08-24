<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\MatriculationNumberHelper;
use App\Http\Controllers\Controller;
use App\Imports\StudentImport;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use App\Mail\StudentAccountCreated;
use App\Services\AcademicCacheService;
use App\Exports\StudentsExport;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(new StudentsExport($request->all()), 'students_export_' . now()->format('Y_m_d_His') . '.xlsx');
    }

    public function create()
    {
        return Inertia::render('Admin/Students/Create', [
            'sessions' => AcademicCacheService::getSessions(),
            'faculties' => AcademicCacheService::getFaculties(),
            'programmes' => AcademicCacheService::getProgrammes(),
            'states' => AcademicCacheService::getStates(),
            'levels' => ['100', '200', '300', '400', '500'],
            'entry_modes' => ['UTME', 'Direct Entry', 'Transfer', 'Postgraduate'],
            'scholarships' => AcademicCacheService::getScholarships(),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->scholarship_id === 'none' || $request->scholarship_id === '' || $request->scholarship_id === 'null') {
            $request->merge(['scholarship_id' => null]);
        }
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'address' => 'required|string',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:lgas,id',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_relationship' => 'nullable|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
            'program_id' => 'required|exists:programmes,id',
            'current_level' => 'required|in:100,200,300,400,500',
            'admitted_session_id' => 'required|exists:academic_sessions,id',
            'entry_mode' => 'required|string',
            'matriculation_number' => 'nullable|string|unique:students,matriculation_number',
            'jamb_registration_number' => 'nullable|string|max:255',
            'jamb_score' => 'nullable|integer',
            'previous_institution' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'passport_photo' => 'nullable|image|max:1024',
            'waec_result' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
            'fee_policy' => 'required|in:admission_session,current_session',
            'scholarship_id' => 'nullable|exists:scholarships,id',
        ]);

        $password = $request->password ?? Str::random(10);

        DB::transaction(function () use ($validated, $request, $password) {
            // Create User
            $user = \App\Models\User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ]);

            $user->assignRole('student');

            // Generate Matric Number
            $dept = \App\Models\Department::find($validated['department_id']);
            $matricNumber = !empty($validated['matriculation_number'])
                ? strtoupper(trim($validated['matriculation_number']))
                : MatriculationNumberHelper::generate([
                    'dept_code' => $dept?->code,
                    'level' => $validated['current_level'],
                ]);

            // Handle Passport
            $passportPath = null;
            if ($request->hasFile('passport_photo')) {
                $passportPath = $request->file('passport_photo')->store('passports', 'public');
            }

            $prog = \App\Models\Programme::find($validated['program_id']);
            $entryLevel = (int) $validated['current_level'];
            $duration = max(($prog?->duration ?? 4) - ($entryLevel === 200 ? 1 : ($entryLevel === 300 ? 2 : 0)), 1);

            // Create Student Profile
            $student = Student::create([
                'user_id' => $user->id,
                'matriculation_number' => $matricNumber,
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'state_id' => $validated['state_id'],
                'lga_id' => $validated['lga_id'],
                'next_of_kin_name' => $validated['next_of_kin_name'] ?? null,
                'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
                //                'next_of_kin_relationship' => $validated['next_of_kin_relationship'] ?? null,
                'faculty_id' => $validated['faculty_id'],
                'department_id' => $validated['department_id'],
                'program_id' => $validated['program_id'],
                'current_level' => $validated['current_level'],
                'admitted_session_id' => $validated['admitted_session_id'],
                'entry_mode' => $validated['entry_mode'],
                'jamb_registration_number' => $validated['jamb_registration_number'] ?? null,
                'previous_institution' => $validated['previous_institution'] ?? null,
                'passport_photo_path' => $passportPath,
                'fee_policy' => $validated['fee_policy'],
                'scholarship_id' => $validated['scholarship_id'] ?? null,
                'program_duration' => $duration,
            ]);

            $currentSession = \App\Models\Session::find($validated['admitted_session_id']);

            $currenSemester = $currentSession ? $currentSession->semesters()->where('is_current', true)->first() : null;

            StudentSession::create([
                'student_id' => $student->id,
                'session_id' => $validated['admitted_session_id'],
                'level' => $validated['current_level'],
                'status' => 'active',
                'semester' => $currenSemester?->name ?? 'First Semester',
            ]);

            // Handle WAEC Result
            //             if ($request->hasFile('waec_result')) {
            //                 $waecPath = $request->file('waec_result')->store('documents/waec', 'public');
            //                 $student->oLevelResults()->create([
            //                     'exam_type' => 'WAEC/NECO',
            //                     'exam_year' => date('Y'), // Default to current year for admin onboarding
            //                     'scanned_copy_path' => $waecPath,
            //                     'subjects' => [], // Empty subjects as we're just uploading the doc
            //                 ]);
            //             }

            Mail::to($user->email)->send(new StudentAccountCreated($user, $password));

            // Auto-generate school fee invoice
            $feeService = app(\App\Services\Finance\FeeService::class);
            $feeService->generateSchoolFeeInvoice($student, $currentSession);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully.');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Base query for counts/stats (unfiltered by search/pagination)
        $statsQuery = Student::query();
        if (!$user->can('manage_users')) {
            $statsQuery->whereHas('registrations', function ($q) use ($user) {
                $q->whereHas('course', function ($cq) use ($user) {
                    $cq->whereHas('allocations', function ($aq) use ($user) {
                        $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                    });
                });
            });
        }

        $query = Student::query()
            ->with(['user', 'academicDepartment.faculty', 'admittedSession', 'program', 'scholarship']);

        // Access Control: Lecturers see only students registered in their allocated courses
        if (!$user->can('manage_users')) {
            $query->whereHas('registrations', function ($q) use ($user) {
                $q->whereHas('course', function ($cq) use ($user) {
                    $cq->whereHas('allocations', function ($aq) use ($user) {
                        $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                    });
                });
            });
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Session Filter (Admitted Session)
        if ($request->filled('session_id') && $request->session_id !== 'ALL_SESSIONS') {
            $query->where('admitted_session_id', $request->session_id);
        }

        // Faculty Filter
        if ($request->filled('faculty_id') && $request->faculty_id !== 'ALL_FACULTIES') {
            $query->whereHas('academicDepartment', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        // Department Filter
        if ($request->filled('department_id') && $request->department_id !== 'ALL_DEPARTMENTS') {
            $query->where('department_id', $request->department_id);
        }

        // Level Filter
        if ($request->filled('level') && $request->level !== 'ALL_LEVELS') {
            $query->where('current_level', $request->level);
        }

        // Program Filter
        if ($request->filled('program_id') && $request->program_id !== 'ALL_PROGRAMS') {
            $query->where('program_id', $request->program_id);
        } elseif ($request->filled('program')) {
            // Fallback for string search if needed, or legacy
            $query->whereHas('program', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->program}%");
            });
        }

        // Scholarship Filter
        if ($request->filled('scholarship_id') && $request->scholarship_id !== 'ALL_SCHOLARSHIPS') {
            if ($request->scholarship_id === 'NONE' || $request->scholarship_id === 'none') {
                $query->whereNull('scholarship_id');
            } else {
                $query->where('scholarship_id', $request->scholarship_id);
            }
        }

        // Date Range Filter
        if ($request->filled('date_from')) {
            $query->whereDate('students.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('students.created_at', '<=', $request->date_to);
        }

        // Gender Filter
        if ($request->filled('gender') && $request->gender !== 'ALL_GENDERS' && $request->gender !== 'all') {
            $query->where('gender', strtolower($request->gender));
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'ALL_STATUS' && $request->status !== 'all') {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            });
        }

        // Entry Mode Filter
        if ($request->filled('entry_mode') && $request->entry_mode !== 'ALL_MODES' && $request->entry_mode !== 'all') {
            $query->where('entry_mode', $request->entry_mode);
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');

        if ($sortBy === 'name') {
            $query->join('users', 'students.user_id', '=', 'users.id')
                ->select('students.*')
                ->orderBy('users.name', $sortOrder);
        } elseif ($sortBy === 'matriculation_number') {
            $query->orderBy('students.matriculation_number', $sortOrder);
        } elseif ($sortBy === 'level') {
            $query->orderBy('students.current_level', $sortOrder);
        } else {
            $query->orderBy('students.created_at', $sortOrder);
        }

        $perPage = $request->integer('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $students = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $request->only([
                'search', 'session_id', 'faculty_id', 'department_id', 'level',
                'program_id', 'program', 'scholarship_id', 'date_from', 'date_to',
                'gender', 'status', 'entry_mode', 'sort_by', 'sort_order', 'per_page'
            ]),
            'sessions' => fn() => AcademicCacheService::getSessions(),
            'faculties' => fn() => AcademicCacheService::getFaculties(),
            'departments' => fn() => AcademicCacheService::getAllDepartments(),
            'programmes' => fn() => AcademicCacheService::getProgrammes(),
            'scholarships' => fn() => AcademicCacheService::getScholarships(),
            'stats' => fn() => \Illuminate\Support\Facades\Cache::remember(
                'students_stats_' . ($user->can('manage_users') ? 'admin' : $user->id),
                60 * 5, // Cache for 5 minutes
                function () use ($statsQuery) {
                    return [
                        'total' => (clone $statsQuery)->count(),
                        'new' => (clone $statsQuery)->where('admitted_session_id', Session::latest('start_date')->value('id'))->count(),
                        'graduating' => (clone $statsQuery)->whereIn('current_level', ['400', '500', '600'])->count(),
                    ];
                }
            ),
        ]);
    }

    public function show(Student $student)
    {
        $user = auth()->user();
        
        // Authorization check for lecturers
        if (!$user->can('manage_users')) {
            $isAuthorized = $student->registrations()->whereHas('course', function ($q) use ($user) {
                $q->whereHas('allocations', function ($aq) use ($user) {
                    $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                });
            })->exists();

            if (!$isAuthorized) {
                abort(403, 'You are not authorized to view this student.');
            }
        }

        $canViewFinance = $user->can('view_payments');
        $canViewAcademics = $user->can('view_results');

        $student->load([
            'user',
            'academicDepartment.faculty',
            'admittedSession',
            'program',
            'state',
            'lga',
            'scholarship',
            'sessions.session',
            'oLevelResults',
            'hostelBookings.session',
            'hostelBookings.invoice',
            'hostelBookings.room.floor.block.hostel',
        ]);

        if ($canViewFinance) {
            $student->load(['user.invoices.session', 'user.invoices.payments', 'user.invoices.items', 'user.payments']);
        }

        if ($canViewAcademics) {
            $student->load([
                'registrations.course',
                'registrations.session',
                'registrations.semester',
            ]);
        }

        $academicHistory = $canViewAcademics ? $student->registrations
            ->sortByDesc('session.start_date')
            ->groupBy(fn($reg) => $reg->session?->name ?? 'Unknown Session')
            ->map(function ($sessionRegs) {
                return $sessionRegs->groupBy(fn($reg) => $reg->semester ? $reg->semester->name : 'Unknown Semester');
            }) : null;

        return Inertia::render('Admin/Students/Show', [
            'student' => $student,
            'academicHistory' => $academicHistory,
            'financialHistory' => $canViewFinance ? [
                'invoices' => $student->user->invoices->sortByDesc('created_at')->values(),
                'payments' => $student->user->payments->sortByDesc('paid_at')->values(),
            ] : null,
            'permissions' => [
                'can_view_finance' => $canViewFinance,
                'can_view_academics' => $canViewAcademics,
                'can_edit_admission' => $user->hasRole('admission_director') || $user->hasRole('admin'),
                'can_edit_students' => $user->can('edit_students'),
                'can_perform_registration' => $user->can('perform_student_registration'),
                'manage_student_registrations' => $user->can('manage_student_registrations') || $user->can('fix_course_registration'),
                'can_reset_password' => $user->can('reset_student_password') || $user->can('edit_students'),
            ],
            'sessions' => ($user->hasRole('admission_director') || $user->hasRole('admin') || $user->can('edit_students')) 
                ? AcademicCacheService::getSessions() 
                : [],
        ]);
    }

    public function updateAdmissionSession(Request $request, Student $student)
    {
        if (!auth()->user()->hasRole(['admission_director', 'admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'admitted_session_id' => 'required|exists:academic_sessions,id',
        ]);

        $student->update([
            'admitted_session_id' => $validated['admitted_session_id'],
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Admission session updated successfully.');
    }

    public function import(Request $request)
    {
        if ($request->scholarship_id === 'none') {
            $request->merge(['scholarship_id' => null]);
        }

        $request->validate([
            'file'          => 'required|mimes:csv,txt,xlsx|max:10240',
            'session_id'    => 'required|exists:academic_sessions,id',
            'faculty_id'    => 'nullable|exists:faculties,id',
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programmes,id',
            'level'         => 'nullable|in:100,200,300,400,500',
            'scholarship_id'=> 'nullable|exists:scholarships,id',
        ]);

        try {
            // Prevent timeout on larger import files
            set_time_limit(180);

            // Temporarily lower hashing cost for fast import speed
            config(['hashing.bcrypt.rounds' => 4]);

            $import = new StudentImport(
                $request->faculty_id    ?: null,
                $request->department_id ?: null,
                $request->program_id    ?: null,
                $request->session_id,
                $request->level         ?: null,
                $request->scholarship_id
            );
            Excel::import($import, $request->file('file'));

            return redirect()->route('admin.students.index')->with('success', $import->getProcessedCount() . ' students imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.students.index')->with('error', 'Error during import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function collection()
            {
                return collect([
                    [
                        'first_name'           => 'John',
                        'last_name'            => 'Doe',
                        'email'                => 'john.doe@example.com',
                        'phone_number'         => '08012345678',
                        'gender'               => 'male',
                        'dob'                  => '2000-01-01',
                        'address'              => '123 University Road',
                        'state'                => 'Lagos',
                        'lga'                  => 'Ikeja',
                        'entry_mode'           => 'UTME',
                        'matric_number'        => 'UNI/2024/0001',
                        'jamb_reg'             => '2024123456AB',
                        'jamb_score'           => '280',
                        'previous_institution' => '',
                        'programme'            => 'Computer Science',  // Used if Programme not selected on form
                        'level'                => '100',               // Used if Level not selected on form
                        'scholarship'          => 'Full Tuition',      // Optional: name of scholarship
                    ]
                ]);
            }

            public function headings(): array
            {
                return [
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'gender',
                    'dob',
                    'address',
                    'state',
                    'lga',
                    'entry_mode',
                    'matric_number',
                    'jamb_reg',
                    'jamb_score',
                    'previous_institution',
                    'programme',   // Optional: overridden by form selection
                    'level',       // Optional: overridden by form selection
                    'scholarship', // Optional: name of scholarship
                ];
            }
        };

        return Excel::download($export, 'student_import_template.xlsx');
    }

    public function edit(Student $student)
    {
        $student->load(['user', 'academicDepartment.faculty', 'admittedSession', 'program', 'state', 'lga']);

        $nameParts = explode(' ', $student->user->name, 2);
        $student->first_name = $nameParts[0] ?? '';
        $student->last_name = $nameParts[1] ?? '';
        
        return Inertia::render('Admin/Students/Edit', [
            'student' => $student,
            'can_edit_name_email' => auth()->user()->can('edit_student_name_email'),
            'sessions' => AcademicCacheService::getSessions(),
            'faculties' => AcademicCacheService::getFaculties(),
            'programmes' => AcademicCacheService::getProgrammes(),
            'states' => AcademicCacheService::getStates(),
            'levels' => ['100', '200', '300', '400', '500'],
            'entry_modes' => ['UTME', 'Direct Entry', 'Transfer', 'Postgraduate'],
            'scholarships' => AcademicCacheService::getScholarships(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        if ($request->scholarship_id === 'none' || $request->scholarship_id === '' || $request->scholarship_id === 'null') {
            $request->merge(['scholarship_id' => null]);
        }
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'address' => 'required|string',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:lgas,id',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
            'program_id' => 'required|exists:programmes,id',
            'current_level' => 'required|in:100,200,300,400,500',
            'admitted_session_id' => 'required|exists:academic_sessions,id',
            'entry_mode' => 'required|string',
            'matriculation_number' => 'required|string|unique:students,matriculation_number,' . $student->id,
            'jamb_registration_number' => 'nullable|string|max:255',
            'jamb_score' => 'nullable|integer',
            'previous_institution' => 'nullable|string|max:255',
            'fee_policy' => 'required|in:admission_session,current_session',
            'scholarship_id' => 'nullable|exists:scholarships,id',
        ]);

        $canEditNameEmail = $request->user()->can('edit_student_name_email');
        
        $nameParts = explode(' ', $student->user->name, 2);
        $oldFirstName = $nameParts[0] ?? '';
        $oldLastName = $nameParts[1] ?? '';
        $oldEmail = $student->user->email;

        $hasNameChanged = ($request->first_name !== $oldFirstName) || ($request->last_name !== $oldLastName);
        $hasEmailChanged = $request->email !== $oldEmail;

        if (($hasNameChanged || $hasEmailChanged) && !$canEditNameEmail) {
            abort(403, 'You do not have permission to edit the student name or email.');
        }

        DB::transaction(function () use ($validated, $student, $request) {
            $student->user->update([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
            ]);

            if ($request->filled('password')) {
                $student->user->update([
                    'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                ]);
            }

            $student->update([
                'matriculation_number' => strtoupper(trim($validated['matriculation_number'])),
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'state_id' => $validated['state_id'],
                'lga_id' => $validated['lga_id'],
                'next_of_kin_name' => $validated['next_of_kin_name'] ?? null,
                'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
                'faculty_id' => $validated['faculty_id'],
                'department_id' => $validated['department_id'],
                'program_id' => $validated['program_id'],
                'current_level' => $validated['current_level'],
                'admitted_session_id' => $validated['admitted_session_id'],
                'entry_mode' => $validated['entry_mode'],
                'jamb_registration_number' => $validated['jamb_registration_number'] ?? null,
                'jamb_score' => $validated['jamb_score'] ?? null,
                'previous_institution' => $validated['previous_institution'] ?? null,
                'fee_policy' => $validated['fee_policy'],
                'scholarship_id' => $validated['scholarship_id'] ?? null,
            ]);

            // Sync the active session's level to match the new current_level
            $activeSession = $student->currentSession()->first();
            if ($activeSession) {
                $activeSession->update(['level' => $validated['current_level']]);
            }
        });

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    /**
     * Manually promote a student to the next level and process their session.
     */
    public function promote(Request $request, Student $student)
    {
        $currentSession = Session::current();
        
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found for promotion.');
        }

        $semesterName = $currentSession->semesters()->where('is_current', true)->value('name') ?? 'First Semester';

        try {
            // We use the Job logic directly or dispatch it
            // For immediate feedback in UI, we can run it synchronously
            app(\App\Jobs\Academic\ProcessStudentSessionJob::class, [
                'student' => $student,
                'session' => $currentSession,
                'semesterName' => $semesterName
            ])->handle(app(\App\Services\Finance\FeeService::class));

            return back()->with('success', "Student promoted to level {$student->fresh()->current_level} for session {$currentSession->name}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Promotion failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle a student user active/deactive status.
     */
    public function toggleStatus(Request $request, Student $student)
    {
        if (!$request->user()->can('edit_students')) {
            abort(403, 'Unauthorized action.');
        }

        $user = $student->user;
        $newStatus = !$user->is_active;

        $user->update(['is_active' => $newStatus]);

        activity('student')
            ->performedOn($student)
            ->causedBy(auth()->user())
            ->withProperties([
                'student_name' => $user->name,
                'status' => $newStatus ? 'activated' : 'deactivated',
            ])
            ->log("Student account " . ($newStatus ? 'activated' : 'deactivated'));

        $statusText = $newStatus ? 'activated' : 'deactivated';
        return back()->with('success', "Student account has been successfully {$statusText}.");
    }

    /**
     * Bulk assign scholarship to multiple students.
     */
    public function bulkAssignScholarship(Request $request)
    {
        if (!$request->user()->can('edit_students')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:students,id',
            'scholarship_id' => 'nullable|exists:scholarships,id',
        ]);

        $scholarshipId = $validated['scholarship_id'] ?? null;

        // Perform mass update
        Student::whereIn('id', $validated['student_ids'])->update([
            'scholarship_id' => $scholarshipId,
        ]);

        // Log activity for each student
        $students = Student::whereIn('id', $validated['student_ids'])->with('user')->get();
        foreach ($students as $student) {
            activity('student')
                ->performedOn($student)
                ->causedBy(auth()->user())
                ->withProperties([
                    'student_name' => $student->user->name,
                    'scholarship_id' => $scholarshipId,
                ])
                ->log("Scholarship assigned/updated in bulk");
        }

        return back()->with('success', count($validated['student_ids']) . ' students updated successfully.');
    }

    /**
     * Search students for bulk scholarship assignment.
     */
    public function searchBulk(Request $request)
    {
        if (!$request->user()->can('edit_students')) {
            abort(403, 'Unauthorized action.');
        }

        $query = $request->query('query');
        if (empty($query)) {
            return response()->json([]);
        }

        $students = Student::with('user')
            ->where(function ($q) use ($query) {
                $q->where('matriculation_number', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($uq) use ($query) {
                      $uq->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->user->name,
                    'matriculation_number' => $student->matriculation_number,
                    'email' => $student->user->email,
                ];
            });

        return response()->json($students);
    }

    public function resetPassword(Student $student)
    {
        if (!auth()->user()->can('reset_student_password') && !auth()->user()->can('edit_students')) {
            abort(403, 'Unauthorized action.');
        }

        $newPassword = Str::random(10);
        $student->user->update([
            'password' => Hash::make($newPassword),
        ]);

        activity('student')
            ->performedOn($student)
            ->causedBy(auth()->user())
            ->withProperties([
                'student_id' => $student->id,
                'student_name' => $student->user->name,
                'reset_by' => auth()->user()->name,
            ])
            ->log("Password reset for student {$student->user->name}");

        try {
            Mail::to($student->user->email)->send(new StudentAccountCreated($student->user, $newPassword, $student->matriculation_number));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send reset password email to student: ' . $e->getMessage());
        }

        return back()->with('success', "Password reset successfully for {$student->user->name}. New password: {$newPassword}");
    }
}
