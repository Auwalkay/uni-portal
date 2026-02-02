<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function create()
    {
        return \Inertia\Inertia::render('Admin/Students/Create', [
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
            'faculties' => \App\Models\Faculty::with('departments:id,name,faculty_id')->get(['id', 'name']),
            'programmes' => \App\Models\Programme::orderBy('name')->get(['id', 'name']),
            'states' => \App\Models\State::with('lgas:id,name,state_id')->get(['id', 'name']),
            'levels' => ['100', '200', '300', '400', '500'],
            'entry_modes' => ['UTME', 'Direct Entry', 'Transfer', 'Postgraduate'],
        ]);
    }

    public function store(Request $request)
    {
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
            'jamb_registration_number' => 'nullable|string|max:255',
            'jamb_score' => 'nullable|integer',
            'previous_institution' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'passport_photo' => 'nullable|image|max:2048',
            'waec_result' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            // Create User
            $user = \App\Models\User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password'] ?? 'password'),
            ]);

            $user->assignRole('student');

            // Generate Matric Number
            $year = date('Y');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $matricNumber = "UNI/{$year}/{$random}";

            while (\App\Models\Student::where('matriculation_number', $matricNumber)->exists()) {
                $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $matricNumber = "UNI/{$year}/{$random}";
            }

            // Handle Passport
            $passportPath = null;
            if ($request->hasFile('passport_photo')) {
                $passportPath = $request->file('passport_photo')->store('passports', 'public');
            }

            // Create Student Profile
            $student = \App\Models\Student::create([
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
                'next_of_kin_relationship' => $validated['next_of_kin_relationship'],
                'faculty_id' => $validated['faculty_id'],
                'department_id' => $validated['department_id'],
                'program_id' => $validated['program_id'],
                'current_level' => $validated['current_level'],
                'admitted_session_id' => $validated['admitted_session_id'],
                'entry_mode' => $validated['entry_mode'],
                'jamb_registration_number' => $validated['jamb_registration_number'],
                'jamb_score' => $validated['jamb_score'],
                'previous_institution' => $validated['previous_institution'],
                'passport_photo_path' => $passportPath,
            ]);

            // Handle WAEC Result
            if ($request->hasFile('waec_result')) {
                $waecPath = $request->file('waec_result')->store('documents/waec', 'public');
                $student->oLevelResults()->create([
                    'exam_type' => 'WAEC/NECO',
                    'exam_year' => $year, // Default to current year for admin onboarding
                    'scanned_copy_path' => $waecPath,
                    'subjects' => [], // Empty subjects as we're just uploading the doc
                ]);
            }
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully.');
    }

    public function index(Request $request)
    {
        $query = \App\Models\Student::query()
            ->with(['user', 'academicDepartment.faculty', 'admittedSession', 'program']);

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

        $students = $query->latest()->paginate(15)->withQueryString();

        return \Inertia\Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $request->only(['search', 'session_id', 'faculty_id', 'department_id', 'level', 'program_id', 'program']),
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
            'faculties' => \App\Models\Faculty::with('departments:id,name,faculty_id')->get(['id', 'name']),
            'departments' => \App\Models\Department::get(['id', 'name', 'faculty_id']),
            'programmes' => \App\Models\Programme::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => \App\Models\Student::count(),
                // Assuming 'new' means admitted in the latest session. 
                // We'll dynamically determine the latest session or just check created_at for this year if session isn't reliable yet.
                // Using latest session is safer for academic context.
                'new' => \App\Models\Student::where('admitted_session_id', \App\Models\Session::latest('start_date')->value('id'))->count(),
                // Assuming graduating levels are 400, 500, 600
                'graduating' => \App\Models\Student::whereIn('current_level', ['400', '500', '600'])->count(),
            ]
        ]);
    }

    public function show(\App\Models\Student $student)
    {
        $user = auth()->user();
        $canViewFinance = $user->hasAnyPermission(['manage_payments', 'verify_payments', 'view_payments']) || $user->hasRole('admin');
        $canViewAcademics = $user->hasAnyPermission(['manage_results', 'approve_results', 'view_results', 'manage_courses', 'assign_coordinators']) || $user->hasRole('admin');

        $student->load([
            'academicDepartment.faculty',
            'admittedSession',
            'program',
            'state',
            'lga',
        ]);

        if ($canViewFinance) {
            $student->load(['user.invoices.session', 'user.payments']);
        }

        if ($canViewAcademics) {
            $student->load([
                'registrations.course',
                'registrations.session',
                'registrations.semester'
            ]);
        }

        $academicHistory = $canViewAcademics ? $student->registrations
            ->sortByDesc('session.start_date')
            ->groupBy(fn($reg) => $reg->session?->name ?? 'Unknown Session')
            ->map(function ($sessionRegs) {
                return $sessionRegs->groupBy(fn($reg) => $reg->semester ? $reg->semester->name : 'Unknown Semester');
            }) : null;

        return \Inertia\Inertia::render('Admin/Students/Show', [
            'student' => $student,
            'academicHistory' => $academicHistory,
            'financialHistory' => $canViewFinance ? [
                'invoices' => $student->user->invoices->sortByDesc('created_at')->values(),
                'payments' => $student->user->payments->sortByDesc('paid_at')->values()
            ] : null,
            'permissions' => [
                'can_view_finance' => $canViewFinance,
                'can_view_academics' => $canViewAcademics,
            ]
        ]);
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240',
        ]);

        try {
            $import = new \App\Imports\StudentImport;
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

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
            'previous_institution'
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
                ''
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=student_import_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }
}
