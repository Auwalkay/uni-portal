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
            'states' => \App\Models\State::with('lgas:id,name,state_id')->get(['id', 'name']),
            'levels' => ['100', '200', '300', '400', '500'],
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
            'next_of_kin_name' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'next_of_kin_relationship' => 'required|string|max:255',
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
            'program' => 'required|string',
            'current_level' => 'required|in:100,200,300,400,500',
            'admitted_session_id' => 'required|exists:sessions,id',
            'password' => 'nullable|string|min:8', // Optional, default is used if empty
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            // Create User
            $user = \App\Models\User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password'] ?? 'password'), // Default password
                'role' => 'student', // Assuming 'student' role exists or is handled by logic
            ]);

            // Generate Matric Number: UNI/YEAR/RANDOM
            $year = date('Y');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $matricNumber = "UNI/{$year}/{$random}";

            // Ensure uniqueness (simple retry logic or just hope for best in demo, 
            // but loop is safer. For now simple check)
            while (\App\Models\Student::where('matriculation_number', $matricNumber)->exists()) {
                $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $matricNumber = "UNI/{$year}/{$random}";
            }

            // Create Student Profile
            \App\Models\Student::create([
                'user_id' => $user->id,
                'matriculation_number' => $matricNumber,
                // Assuming Name is split or handled in User. If Student has name fields:
                // 'first_name' => $validated['first_name'],
                // 'last_name' => $validated['last_name'],
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
                'program' => $validated['program'],
                'current_level' => $validated['current_level'],
                'admitted_session_id' => $validated['admitted_session_id'],
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully.');
    }

    public function index(Request $request)
    {
        $query = \App\Models\Student::query()
            ->with(['user', 'academicDepartment.faculty', 'admittedSession']);

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
        if ($request->filled('program')) {
            $query->where('program', 'like', "%{$request->program}%");
        }

        $students = $query->latest()->paginate(15)->withQueryString();

        return \Inertia\Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $request->only(['search', 'session_id', 'faculty_id', 'department_id', 'level', 'program']),
            'sessions' => \App\Models\Session::latest()->get(['id', 'name']),
            'faculties' => \App\Models\Faculty::with('departments:id,name,faculty_id')->get(['id', 'name']),
            'departments' => \App\Models\Department::get(['id', 'name', 'faculty_id']),
            'stats' => [
                'total' => \App\Models\Student::count(),
                'active' => \App\Models\Student::count(), // Placeholder logic for now
            ]
        ]);
    }

    public function show(\App\Models\Student $student)
    {
        $student->load(['user.invoices.session', 'user.payments', 'academicDepartment.faculty', 'admittedSession', 'registrations.course']);

        return \Inertia\Inertia::render('Admin/Students/Show', [
            'student' => $student,
            'academicHistory' => $student->registrations->groupBy('session_id'), // Group by session later in frontend or here
            'financialHistory' => [
                'invoices' => $student->user->invoices,
                'payments' => $student->user->payments
            ]
        ]);
    }
}
