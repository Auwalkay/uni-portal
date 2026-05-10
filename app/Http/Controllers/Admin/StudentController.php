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
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\StudentAccountCreated;
use App\Services\AcademicCacheService;
use App\Exports\StudentsExport;

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
            'passport_photo' => 'nullable|image|max:2048',
            'waec_result' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
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
            $matricNumber = $validated['matriculation_number'] ?? MatriculationNumberHelper::generate(['dept_code' => $dept?->code]);

            // Handle Passport
            $passportPath = null;
            if ($request->hasFile('passport_photo')) {
                $passportPath = $request->file('passport_photo')->store('passports', 'public');
            }

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
                'next_of_kin_name' => $validated['next_of_kin_name'],
                'next_of_kin_phone' => $validated['next_of_kin_phone'],
                //                'next_of_kin_relationship' => $validated['next_of_kin_relationship'],
                'faculty_id' => $validated['faculty_id'],
                'department_id' => $validated['department_id'],
                'program_id' => $validated['program_id'],
                'current_level' => $validated['current_level'],
                'admitted_session_id' => $validated['admitted_session_id'],
                'entry_mode' => $validated['entry_mode'],
                'jamb_registration_number' => $validated['jamb_registration_number'],
                'previous_institution' => $validated['previous_institution'],
                'passport_photo_path' => $passportPath,
                'fee_policy' => $validated['fee_policy'],
                'scholarship_id' => $validated['scholarship_id'],
            ]);

            $currentSession = \App\Models\Session::find($validated['admitted_session_id']);

            $currenSemester = $currentSession->semesters()->where('is_current', true)->first();

            StudentSession::create([
                'student_id' => $student->id,
                'session_id' => $validated['admitted_session_id'],
                'level' => $validated['current_level'],
                'status' => 'active',
                'semester' => $currenSemester->name,
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
        if ($request->filled('session_id')) {
            $query->where('admitted_session_id', $request->session_id);
        }

        // Faculty Filter
        if ($request->filled('faculty_id')) {
            $query->whereHas('academicDepartment', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        // Department Filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Level Filter
        if ($request->filled('level')) {
            $query->where('current_level', $request->level);
        }

        // Program Filter
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        } elseif ($request->filled('program')) {
            // Fallback for string search if needed, or legacy
            $query->whereHas('program', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->program}%");
            });
        }

        // Scholarship Filter
        if ($request->filled('scholarship_id')) {
            if ($request->scholarship_id === 'NONE' || $request->scholarship_id === 'none') {
                $query->whereNull('scholarship_id');
            } else {
                $query->where('scholarship_id', $request->scholarship_id);
            }
        }

        // Date Range Filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $students = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $request->only(['search', 'session_id', 'faculty_id', 'department_id', 'level', 'program_id', 'program', 'scholarship_id', 'date_from', 'date_to']),
            'sessions' => AcademicCacheService::getSessions(),
            'faculties' => AcademicCacheService::getFaculties(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'programmes' => AcademicCacheService::getProgrammes(),
            'scholarships' => AcademicCacheService::getScholarships(),
            'stats' => [
                'total' => (clone $query)->count(),
                'new' => (clone $query)->where('admitted_session_id', Session::latest('start_date')->value('id'))->count(),
                'graduating' => (clone $query)->whereIn('current_level', ['400', '500', '600'])->count(),
            ],
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

        $canViewFinance = $user->hasAnyPermission(['manage_payments', 'verify_payments', 'view_payments']) || $user->hasRole('admin');
        $canViewAcademics = $user->hasAnyPermission(['manage_results', 'approve_results', 'view_results', 'manage_courses', 'assign_coordinators']) || $user->hasRole('admin');

        $student->load([
            'academicDepartment.faculty',
            'admittedSession',
            'program',
            'state',
            'lga',
            'scholarship',
        ]);

        if ($canViewFinance) {
            $student->load(['user.invoices.session', 'user.payments']);
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
            ],
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240',
        ]);

        try {
            $import = new StudentImport;
            Excel::import($import, $request->file('file'));

            return redirect()->route('admin.students.index')->with('success', $import->getProcessedCount() . ' students imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.students.index')->with('error', 'Error during import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'gender',
            'dob',
            'address',
            'state',
            'lga',
            'faculty',
            'department',
            'programme',
            'session',
            'level',
            'entry_mode',
            'matric_number',
            'jamb_reg',
            'jamb_score',
            'previous_institution',
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            // Add a sample row
            fputcsv($file, [
                'John',
                'Doe',
                'john.doe@example.com',
                '08012345678',
                'male',
                '2000-01-01',
                '123 University Road',
                'Lagos',
                'Ikeja',
                'Faculty of Science',
                'Computer Science',
                'Computer Science (B.Sc)',
                '2024/2025',
                '100',
                'UTME',
                'UNI/2024/0001',
                '2024123456AB',
                '280',
                '',
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=student_import_template.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    public function edit(Student $student)
    {
        $student->load(['user', 'academicDepartment.faculty', 'admittedSession', 'program', 'state', 'lga']);

        $nameParts = explode(' ', $student->user->name, 2);
        $student->first_name = $nameParts[0] ?? '';
        $student->last_name = $nameParts[1] ?? '';
        
        return Inertia::render('Admin/Students/Edit', [
            'student' => $student,
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
                'matriculation_number' => $validated['matriculation_number'],
                'gender' => $validated['gender'],
                'dob' => $validated['dob'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'state_id' => $validated['state_id'],
                'lga_id' => $validated['lga_id'],
                'next_of_kin_name' => $validated['next_of_kin_name'],
                'next_of_kin_phone' => $validated['next_of_kin_phone'],
                'faculty_id' => $validated['faculty_id'],
                'department_id' => $validated['department_id'],
                'program_id' => $validated['program_id'],
                'current_level' => $validated['current_level'],
                'admitted_session_id' => $validated['admitted_session_id'],
                'entry_mode' => $validated['entry_mode'],
                'jamb_registration_number' => $validated['jamb_registration_number'],
                'jamb_score' => $validated['jamb_score'],
                'previous_institution' => $validated['previous_institution'],
                'fee_policy' => $validated['fee_policy'],
                'scholarship_id' => $validated['scholarship_id'],
            ]);
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
}
