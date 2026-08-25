<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withTrashed()->with(['roles', 'staff', 'student']);

        // Search Filter (Search by Name, Email, Staff Number, or Matriculation Number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($sq) use ($search) {
                        $sq->where('staff_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('matriculation_number', 'like', "%{$search}%");
                    });
            });
        }

        // Multi-Role / Single Role Filter
        if ($request->filled('roles')) {
            $roles = is_array($request->roles) ? $request->roles : explode(',', $request->roles);
            $roles = array_filter($roles, fn($r) => !empty($r) && $r !== 'ALL');
            if (count($roles) > 0) {
                $query->whereHas('roles', fn($q) => $q->whereIn('name', $roles));
            }
        } elseif ($request->filled('role') && $request->role !== 'ALL') {
            $query->role($request->role);
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'ALL') {
            switch ($request->status) {
                case 'active':
                    $query->whereNull('deleted_at')->where('is_active', true);
                    break;
                case 'inactive':
                case 'suspended':
                    $query->whereNull('deleted_at')->where('is_active', false);
                    break;
                case 'trashed':
                case 'deleted':
                    $query->onlyTrashed();
                    break;
                case 'verified':
                    $query->whereNull('deleted_at')->whereNotNull('email_verified_at');
                    break;
                case 'unverified':
                    $query->whereNull('deleted_at')->whereNull('email_verified_at');
                    break;
            }
        } else {
            // Default: Hide soft deleted users unless filtering specifically
            $query->whereNull('deleted_at');
        }

        // Sort Options
        $sort = $request->get('sort', 'created_at_desc');
        switch ($sort) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'email_asc':
                $query->orderBy('email', 'asc');
                break;
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'created_at_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Per Page
        $perPage = $request->integer('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $users = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'roles', 'status', 'sort', 'per_page']),
            'availableRoles' => Role::all(['id', 'name']),
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'trashed' => User::onlyTrashed()->count(),
                'admins' => User::role('admin')->count(),
                'staff' => User::role('staff')->count(),
                'students' => User::role('student')->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'staff_number' => 'nullable|string|max:255|unique:staff,staff_number',
            'matriculation_number' => 'nullable|string|max:255|unique:students,matriculation_number',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($request->roles);

        $this->autoProvisionProfiles(
            $user, 
            $request->roles, 
            $request->filled('staff_number') ? $request->staff_number : null,
            $request->filled('matriculation_number') ? $request->matriculation_number : null
        );

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['roles' => $request->roles])
            ->log("Created new system user {$user->name}");

        return back()->with('success', "User '{$user->name}' created successfully.");
    }

    public function show(User $user)
    {
        $user->load([
            'roles.permissions',
            'permissions',
            'staff.department',
            'staff.designation',
            'student.programme.department.faculty',
            'applicant.programme',
        ]);

        $activityLogs = \Spatie\Activitylog\Models\Activity::where(function ($q) use ($user) {
            $q->where(function ($c) use ($user) {
                $c->where('causer_type', User::class)
                  ->where('causer_id', $user->id);
            })->orWhere(function ($s) use ($user) {
                $s->where('subject_type', User::class)
                  ->where('subject_id', $user->id);
            });
        })
        ->with('causer:id,name,email')
        ->latest()
        ->limit(100)
        ->get();

        $allPermissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'user' => $user,
            'activityLogs' => $activityLogs,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $staffId = $user->staff ? $user->staff->id : null;
        $studentId = $user->student ? $user->student->id : null;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'staff_number' => ['nullable', 'string', 'max:255', Rule::unique('staff', 'staff_number')->ignore($staffId)],
            'matriculation_number' => ['nullable', 'string', 'max:255', Rule::unique('students', 'matriculation_number')->ignore($studentId)],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Update Staff Number if provided or existing
        if ($request->filled('staff_number')) {
            if ($user->staff) {
                $user->staff->update(['staff_number' => trim($request->staff_number)]);
            } else {
                \App\Models\Staff::create([
                    'user_id' => $user->id,
                    'staff_number' => trim($request->staff_number),
                    'is_academic' => false,
                ]);
            }
        }

        // Update Matriculation / Reg Number if provided or existing
        if ($request->filled('matriculation_number')) {
            if ($user->student) {
                $user->student->update(['matriculation_number' => trim($request->matriculation_number)]);
            } else {
                $nameParts = explode(' ', trim($user->name));
                \App\Models\Student::create([
                    'user_id' => $user->id,
                    'first_name' => $nameParts[0] ?? $user->name,
                    'last_name' => count($nameParts) > 1 ? end($nameParts) : 'Student',
                    'matriculation_number' => trim($request->matriculation_number),
                    'status' => 'active',
                ]);
            }
        }

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Updated basic details & identifiers for user {$user->name}");

        return back()->with('success', "User details for '{$user->name}' updated successfully.");
    }

    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($request->roles);

        $this->autoProvisionProfiles($user, $request->roles);

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties(['new_roles' => $request->roles])
            ->log("Updated assigned roles for user {$user->name}");

        return back()->with('success', "Roles for user '{$user->name}' updated successfully.");
    }

    private function autoProvisionProfiles(User $user, array $roles, ?string $customStaffNumber = null, ?string $customMatricNumber = null): void
    {
        $nameParts = explode(' ', trim($user->name));
        $firstName = $nameParts[0] ?? $user->name;
        $lastName = count($nameParts) > 1 ? end($nameParts) : 'User';

        // 1. Staff Auto-Provisioning
        $nonStudentRoles = array_diff($roles, ['student', 'applicant']);
        if (count($nonStudentRoles) > 0 && !$user->staff) {
            $isAcademic = count(array_intersect($roles, ['lecturer', 'dean', 'hod', 'course_coordinator'])) > 0;
            $staffNum = $customStaffNumber ? trim($customStaffNumber) : \App\Helpers\StaffNumberHelper::generate();
            \App\Models\Staff::create([
                'user_id' => $user->id,
                'staff_number' => $staffNum,
                'is_academic' => $isAcademic,
            ]);
        }

        // 2. Student Auto-Provisioning
        if (in_array('student', $roles) && !$user->student) {
            $matricNum = $customMatricNumber ? trim($customMatricNumber) : \App\Helpers\MatriculationNumberHelper::generate();
            \App\Models\Student::create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'matriculation_number' => $matricNum,
                'status' => 'active',
            ]);
        }

        // 3. Applicant Auto-Provisioning
        if (in_array('applicant', $roles) && !$user->applicant) {
            $appNo = 'APP/' . date('Y') . '/' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            \App\Models\Applicant::create([
                'user_id' => $user->id,
                'application_number' => $appNo,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'status' => 'pending',
            ]);
        }
    }

    public function resetPassword(Request $request, User $user)
    {
        $password = $request->filled('password') ? $request->password : Str::random(10);

        if ($request->filled('password')) {
            $request->validate(['password' => 'required|string|min:8']);
        }

        $user->update([
            'password' => Hash::make($password)
        ]);

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Reset password for user {$user->name}");

        return back()->with('success', "Password for {$user->name} reset successfully. New password: {$password}");
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', "You cannot suspend your own account.");
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'reactivated' : 'suspended';

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("{$status} user account {$user->name}");

        return back()->with('success', "User account for '{$user->name}' has been {$status}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', "You cannot delete your own account.");
        }

        $userName = $user->name;
        $user->delete(); // Soft deletes record

        activity('user')
            ->causedBy(auth()->user())
            ->log("Soft-deleted user account {$userName}");

        return back()->with('success', "User account '{$userName}' soft-deleted successfully.");
    }

    public function restore(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        activity('user')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Restored soft-deleted user account {$user->name}");

        return back()->with('success', "User account '{$user->name}' restored successfully.");
    }
}
