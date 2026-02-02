<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->get();

        return Inertia::render('Admin/Settings/Roles/Index', [
            'roles' => $roles
        ]);
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $allPermissions = Permission::all()->groupBy(function ($perm) {
            // Group by the first part of the permission name (e.g., 'manage_users' -> 'users')
            $parts = explode('_', $perm->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        });

        return Inertia::render('Admin/Settings/Roles/Edit', [
            'role' => $role,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.settings.roles.index')
            ->with('success', "Permissions for role '{$role->name}' updated successfully.");
    }
}
