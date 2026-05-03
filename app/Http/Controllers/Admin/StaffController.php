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

class StaffController extends Controller
{
    private function getDesignations(): array
    {
        return Cache::remember('staff_designations_list', 3600, function () {
            return Designation::where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();
        });
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
        if ($request->filled('role_id')) {
            $role = \App\Models\Role::find($request->role_id);
            if ($role) {
                $query->role($role->name);
            }
        }

        // Department/Faculty Filter
        if ($request->filled('faculty_id')) {
            $query->whereHas('staff.department', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        if ($request->filled('department_id')) {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $staff = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'filters' => $request->only(['search', 'role_id', 'faculty_id', 'department_id']),
            'faculties' => Cache::remember('faculties_with_departments', 86400, fn() => Faculty::with('departments:id,name,faculty_id')->get(['id', 'name'])),
            'roles' => \App\Models\Role::whereNotIn('name', ['admin', 'student', 'applicant', 'staff'])->get(['id', 'name']),
            'stats' => [
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
            'faculties' => Cache::remember('faculties_with_departments_full', 86400, fn() => Faculty::with('departments')->get()),
            'designations' => $this->getDesignations(),
            'roles' => Role::whereNotIn('name', ['admin', 'student', 'applicant', 'staff'])->get(['id', 'name']),
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
            'staff_number' => 'required|string|max:255|unique:staff',
            'designation' => ['nullable', 'string', Rule::in($this->getDesignations())],
            'department_id' => 'nullable|exists:departments,id',
            'is_academic' => 'boolean',
            'role_id' => 'required|exists:roles,id',
        ]);

        $password = $request->password ?? Str::random(10);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('staff');

        $role = Role::find($request->role_id);
        if ($role) {
            $user->assignRole($role->name);
        }

        $user->staff()->create([
            'staff_number' => $request->staff_number,
            'designation' => $request->designation,
            'department_id' => $request->department_id,
            'is_academic' => $request->is_academic ?? false,
        ]);

        Mail::to($user->email)->send(new StaffAccountCreated($user, $password));

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx|max:10240',
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
        $headers = ['name', 'email', 'staff_number', 'designation', 'department', 'role', 'is_academic'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['John Doe', 'john.doe@example.com', 'STF001', 'Lecturer I', 'Computer Science', 'lecturer', '1']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=staff_import_template.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $staff)
    {
        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        $staff->load(['staff.department.faculty', 'roles', 'staff.allocations.course', 'staff.allocations.session']);

        $timetable = [];
        if ($staff->staff && $staff->staff->is_academic) {
            $currentSession = \App\Models\Session::current();
            if ($currentSession) {
                $courseIds = $staff->staff->allocations->where('session_id', $currentSession->id)->pluck('course_id');

                $timetable = \App\Models\Timetable::whereIn('course_id', $courseIds)
                    ->where('session_id', $currentSession->id)
                    ->with(['course'])
                    ->get();
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

        return Inertia::render('Admin/Staff/Show', [
            'staff' => $staff,
            'timetable' => $timetable,
            'payslips' => $payslips,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $staff)
    {
        $staff->load(['staff.department']);

        if (!$staff->hasRole('staff')) {
            abort(404);
        }

        return Inertia::render('Admin/Staff/Edit', [
            'staff' => $staff,
            'faculties' => Cache::remember('faculties_with_departments_full', 86400, fn() => Faculty::with('departments')->get()),
            'designations' => $this->getDesignations(),
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

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'staff_number' => ['required', 'string', 'max:255', Rule::unique('staff')->ignore($staff->staff->id)],
            'designation' => ['nullable', 'string', Rule::in($this->getDesignations())],
            'department_id' => 'nullable|exists:departments,id',
            'is_academic' => 'boolean',
        ]);

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
            'is_academic' => $request->is_academic ?? false,
        ]);

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

    public function resendAllCredentials()
    {
        \App\Jobs\ResendStaffLoginCredentials::dispatch();

        return back()->with('success', 'Staff credentials resend job has been dispatched. Emails will be sent in the background.');
    }
}
