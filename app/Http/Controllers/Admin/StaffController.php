<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use App\Models\Faculty;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Imports\StaffImport;
use App\Mail\StaffAccountCreated;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Designation;
use Illuminate\Support\Facades\Cache;
use App\Services\AcademicCacheService;
use App\Models\Department;
use App\Exports\StaffExport;

class StaffController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(new StaffExport($request->all()), 'staff_export_' . now()->format('Y_m_d_His') . '.xlsx');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::role('staff')
            ->with(['staff.department.faculty', 'roles']);

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($sq) use ($search) {
                        $sq->where('staff_number', 'like', "%{$search}%");
                    });
            });
        }

        // Role Filter
        if ($request->filled('role_id') && $request->role_id !== 'ALL_ROLES') {
            $role = \App\Models\Role::find($request->role_id);
            if ($role) {
                $query->role($role->name);
            }
        }

        // Department/Faculty Filter
        if ($request->filled('faculty_id') && $request->faculty_id !== 'ALL_FACULTIES') {
            $query->whereHas('staff.department', function ($q) use ($request) {
                if ($request->faculty_id === 'NON_ACADEMIC') {
                    $q->whereNull('faculty_id');
                } else {
                    $q->where('faculty_id', $request->faculty_id);
                }
            });
        }

        if ($request->filled('department_id') && $request->department_id !== 'ALL_DEPARTMENTS') {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Type Filter (academic vs non-academic)
        if ($request->filled('type') && $request->type !== 'ALL_TYPES') {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('is_academic', $request->type === 'academic');
            });
        }

        // Status Filter (active vs inactive)
        if ($request->filled('status') && $request->status !== 'ALL_STATUS') {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorts
        $sort = $request->get('sort', 'name_asc');
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'created_at_desc':
                $query->latest();
                break;
            case 'staff_number':
                $query->whereHas('staff')->orderBy(
                    Staff::select('staff_number')->whereColumn('staff.user_id', 'users.id')->limit(1)
                );
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        // Per Page
        $perPage = $request->integer('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $staff = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'filters' => $request->only(['search', 'role_id', 'faculty_id', 'department_id', 'type', 'status', 'sort', 'per_page']),
            'faculties' => fn() => AcademicCacheService::getFaculties(),
            'nonAcademicDepartments' => fn() => AcademicCacheService::getNonAcademicDepartments(),
            'roles' => fn() => \App\Models\Role::whereNotIn('name', ['student', 'applicant'])->get(['id', 'name']),
            'stats' => fn() => [
                'total' => User::role('staff')->count(),
                'academic' => Staff::where('is_academic', true)->count(),
                'non_academic' => Staff::where('is_academic', false)->count(),
                'roles_count' => DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereNotIn('roles.name', ['admin', 'student', 'applicant', 'staff'])
                    ->groupBy('roles.name')
                    ->select('roles.name', DB::raw('count(*) as count'))
                    ->get(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Staff/Create', [
            'faculties' => AcademicCacheService::getFacultiesFull(),
            'nonAcademicDepartments' => AcademicCacheService::getNonAcademicDepartments(),
            'designations' => AcademicCacheService::getDesignations(),
            'roles' => Role::whereNotIn('name', ['student', 'applicant'])->get(['id', 'name']),
            'states' => AcademicCacheService::getStates(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8', // In production, maybe send invitation link
            'staff_number' => 'nullable|string|max:255|unique:staff,staff_number',
            'designation' => ['nullable', 'string', Rule::in(AcademicCacheService::getDesignations())],
            'department_id' => 'nullable|exists:departments,id',
            'unit_id' => 'nullable|exists:units,id',
            'is_academic' => 'boolean',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'date_joined' => 'nullable|date',
            'highest_qualification' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'nationality' => 'nullable|string|max:100',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:lgas,id',
            'specialization' => 'nullable|string|max:255',
            'research_interests' => 'nullable|string',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'account_name' => 'nullable|string|max:255',
        ]);

        $password = $request->password ?? Str::random(10);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('staff');

        if ($request->filled('role_ids')) {
            $roles = Role::whereIn('id', $request->role_ids)->get();
            foreach ($roles as $role) {
                $user->assignRole($role->name);
            }
        }

        $staffNumber = $request->filled('staff_number') ? $request->staff_number : \App\Helpers\StaffNumberHelper::generate();

        $user->staff()->create([
            'staff_number' => $staffNumber,
            'designation' => $request->designation,
            'department_id' => $request->department_id,
            'unit_id' => $request->unit_id,
            'is_academic' => $request->is_academic ?? false,
            'date_joined' => $request->date_joined,
            'highest_qualification' => $request->highest_qualification,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'marital_status' => $request->marital_status,
            'address' => $request->address,
            'nationality' => $request->nationality,
            'state_id' => $request->state_id,
            'lga_id' => $request->lga_id,
            'specialization' => $request->specialization,
            'research_interests' => $request->research_interests,
            'basic_salary' => $request->basic_salary ?? 0,
            'allowances' => $request->allowances ?? 0,
            'deductions' => $request->deductions ?? 0,
            'bonuses' => $request->bonuses ?? 0,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
        ]);

        Mail::to($user->email)->send(new StaffAccountCreated($user, $password));

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|extensions:csv,xls,xlsx|max:10240',
        ]);

        try {
            $import = new StaffImport;
            Excel::import($import, $request->file('file'));

            return redirect()->route('admin.staff.index')->with('success', $import->getProcessedCount() . ' staff members imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.staff.index')->with('error', 'Error during import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function collection()
            {
                return collect([
                    [
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'staff_number' => 'STF001',
                        'designation' => 'Lecturer I',
                        'department' => 'Computer Science',
                        'role' => 'lecturer',
                        'is_academic' => '1',
                        'phone_number' => '08012345678',
                        'gender' => 'male',
                        'date_of_birth' => '1985-05-15',
                        'marital_status' => 'married',
                        'address' => '123 University Crescent, Campus',
                        'nationality' => 'Nigerian',
                        'state' => 'Lagos',
                        'lga' => 'Ikeja',
                        'highest_qualification' => 'PhD',
                        'date_joined' => '2020-01-10',
                        'specialization' => 'Artificial Intelligence',
                        'research_interests' => 'Machine Learning, Neural Networks',
                    ]
                ]);
            }

            public function headings(): array
            {
                return [
                    'name',
                    'email',
                    'staff_number',
                    'designation',
                    'department',
                    'role',
                    'is_academic',
                    'phone_number',
                    'gender',
                    'date_of_birth',
                    'marital_status',
                    'address',
                    'nationality',
                    'state',
                    'lga',
                    'highest_qualification',
                    'date_joined',
                    'specialization',
                    'research_interests',
                ];
            }
        };

        return Excel::download($export, 'staff_import_template.xlsx');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $staff)
    {
        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $staff->load(['staff.department.faculty', 'roles', 'staff.allocations.course', 'staff.allocations.session']);

        $timetable = [];
        if ($staff->staff && $staff->staff->is_academic) {
            $currentSession = \App\Models\Session::current();
            if ($currentSession) {
                $timetable = \App\Services\AcademicCacheService::getStaffTimetable($staff->staff->id, $currentSession->id);
            }
        }

        $payslips = [];
        if ($staff->staff) {
            $payslips = \App\Models\PayrollItem::where('staff_id', $staff->staff->id)
                ->with(['payroll'])
                ->join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
                ->orderBy('payrolls.month', 'desc')
                ->orderBy('payrolls.year', 'desc')
                ->select('payroll_items.*')
                ->get();
        }

        // Attendance Data with Month & Year Filtering
        $selectedMonth = (int)$request->query('month', now()->month);
        $selectedYear = (int)$request->query('year', now()->year);

        $attendanceStats = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'on_leave' => 0,
            'total' => 0,
            'rate' => 0,
        ];

        $weeklyAttendance = [];

        if ($staff->staff) {
            $attendances = \App\Models\Attendance::where('staff_id', $staff->staff->id)
                ->whereMonth('date', $selectedMonth)
                ->whereYear('date', $selectedYear)
                ->orderBy('date', 'asc')
                ->get();

            $attendanceStats['present'] = $attendances->where('status', 'present')->count();
            $attendanceStats['late'] = $attendances->where('status', 'late')->count();
            $attendanceStats['absent'] = $attendances->where('status', 'absent')->count();
            $attendanceStats['on_leave'] = $attendances->where('status', 'on_leave')->count();
            $attendanceStats['total'] = $attendances->count();
            $attendanceStats['rate'] = $attendanceStats['total'] > 0 
                ? round((($attendanceStats['present'] + $attendanceStats['late']) / $attendanceStats['total']) * 100, 1)
                : 0;

            // Group attendances by week starting Monday
            $grouped = $attendances->groupBy(function ($item) {
                $carbon = \Carbon\Carbon::parse($item->date);
                $startOfWeek = $carbon->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                $endOfWeek = $carbon->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                return 'Week of ' . $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M, Y');
            });

            foreach ($grouped as $weekLabel => $items) {
                $weeklyAttendance[] = [
                    'week' => $weekLabel,
                    'start_date' => \Carbon\Carbon::parse($items->first()->date)->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'),
                    'records' => $items->map(fn($item) => [
                        'id' => $item->id,
                        'date' => \Carbon\Carbon::parse($item->date)->format('Y-m-d'),
                        'day_name' => \Carbon\Carbon::parse($item->date)->format('l'),
                        'formatted_date' => \Carbon\Carbon::parse($item->date)->format('d M, Y'),
                        'clock_in' => $item->clock_in ? \Carbon\Carbon::parse($item->clock_in)->format('H:i') : null,
                        'clock_out' => $item->clock_out ? \Carbon\Carbon::parse($item->clock_out)->format('H:i') : null,
                        'status' => $item->status,
                        'notes' => $item->notes,
                    ])->values(),
                    'present_count' => $items->whereIn('status', ['present', 'late'])->count(),
                    'total_count' => $items->count(),
                ];
            }
        }

        return Inertia::render('Admin/Staff/Show', [
            'staff' => $staff,
            'timetable' => $timetable,
            'payslips' => $payslips,
            'attendanceData' => [
                'weekly' => $weeklyAttendance,
                'stats' => $attendanceStats,
                'filters' => [
                    'month' => $selectedMonth,
                    'year' => $selectedYear,
                ],
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $staff)
    {
        $staff->load(['staff.department', 'staff.unit']);

        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $currentRoles = $staff->roles->whereNotIn('name', ['student', 'applicant', 'staff']);

        return Inertia::render('Admin/Staff/Edit', [
            'staff' => $staff,
            'faculties' => AcademicCacheService::getFacultiesFull(),
            'nonAcademicDepartments' => AcademicCacheService::getNonAcademicDepartments(),
            'designations' => AcademicCacheService::getDesignations(),
            'roles' => Role::whereNotIn('name', ['student', 'applicant'])->get(['id', 'name']),
            'current_role_ids' => $currentRoles->pluck('id')->map(fn($id) => (string)$id)->toArray(),
            'states' => AcademicCacheService::getStates(),
            'canAssignRoles' => auth()->user()->can('assign_staff_roles') || auth()->user()->can('manage_staff'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $staff)
    {
        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $canAssignRoles = auth()->user()->can('assign_staff_roles') || auth()->user()->can('manage_staff');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'staff_number' => ['required', 'string', 'max:255', Rule::unique('staff')->ignore($staff->staff->id)],
            'designation' => ['nullable', 'string', Rule::in(AcademicCacheService::getDesignations())],
            'department_id' => 'nullable|exists:departments,id',
            'unit_id' => 'nullable|exists:units,id',
            'is_academic' => 'boolean',
            'role_ids' => $canAssignRoles ? 'required|array|min:1' : 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'date_joined' => 'nullable|date',
            'highest_qualification' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'marital_status' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'nationality' => 'nullable|string|max:100',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:lgas,id',
            'specialization' => 'nullable|string|max:255',
            'research_interests' => 'nullable|string',
        ];

        $request->validate($rules);

        $staff->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8',
            ]);
            $staff->update([
                'password' => Hash::make($request->password),
            ]);
        }

        $staff->staff()->update([
            'staff_number' => $request->staff_number,
            'designation' => $request->designation,
            'department_id' => $request->department_id,
            'unit_id' => $request->unit_id,
            'is_academic' => $request->is_academic ?? false,
            'date_joined' => $request->date_joined,
            'highest_qualification' => $request->highest_qualification,
            'phone_number' => $request->phone_number,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'marital_status' => $request->marital_status,
            'address' => $request->address,
            'nationality' => $request->nationality,
            'state_id' => $request->state_id,
            'lga_id' => $request->lga_id,
            'specialization' => $request->specialization,
            'research_interests' => $request->research_interests,
        ]);

        // Update Roles if user has permission
        if ($canAssignRoles && $request->filled('role_ids')) {
            $roleNames = Role::whereIn('id', $request->role_ids)->pluck('name')->toArray();
            $staff->syncRoles(array_merge(['staff'], $roleNames));
        }

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $staff)
    {
        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member deleted successfully.');
    }

    /**
     * Toggle a staff user active/deactive status.
     */
    public function toggleStatus(Request $request, User $staff)
    {
        if (!$request->user()->can('manage_staff')) {
            abort(403, 'Unauthorized action.');
        }

        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $newStatus = !$staff->is_active;
        $staff->update(['is_active' => $newStatus]);

        activity('staff')
            ->performedOn($staff)
            ->causedBy(auth()->user())
            ->withProperties([
                'staff_name' => $staff->name,
                'status' => $newStatus ? 'activated' : 'deactivated',
            ])
            ->log("Staff account " . ($newStatus ? 'activated' : 'deactivated'));

        $statusText = $newStatus ? 'activated' : 'deactivated';
        return back()->with('success', "Staff account has been successfully {$statusText}.");
    }

    public function resetPassword(User $staff)
    {
        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $password = Str::random(10);
        $staff->update([
            'password' => Hash::make($password)
        ]);

        Mail::to($staff->email)->send(new StaffAccountCreated($staff, $password));

        return back()->with('success', "Password reset successfully. New credentials sent to {$staff->email}");
    }

    public function resendAllCredentials()
    {
        \App\Jobs\ResendStaffLoginCredentials::dispatch();

        return back()->with('success', 'Staff credentials resend job has been dispatched. Emails will be sent in the background.');
    }
}
