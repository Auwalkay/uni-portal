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
        $roles = Role::withCount('users')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Settings/Roles/Index', [
            'roles' => $roles
        ]);
    }

    public function create()
    {
        $allPermissions = Permission::all()->groupBy(function ($perm) {
            $parts = explode('_', $perm->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        });

        return Inertia::render('Admin/Settings/Roles/Create', [
            'allPermissions' => $allPermissions,
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

        // Ensure permissions are passed as an array of names for easy consumption
        $roleData = $role->toArray();
        $roleData['permissions'] = $role->permissions->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->toArray();

        return Inertia::render('Admin/Settings/Roles/Edit', [
            'role' => $roleData,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Convert name to lowercase and replace spaces with underscores
        $name = strtolower(str_replace(' ', '_', $request->name));

        $role = Role::create(['name' => $name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.settings.roles.index')
            ->with('success', "Role '{$request->name}' created successfully with assigned permissions.");
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
