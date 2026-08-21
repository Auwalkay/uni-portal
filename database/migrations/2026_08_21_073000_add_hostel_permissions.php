<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create permissions
        $permissions = [
            'create_hostels',
            'manage_hostel_fees',
            'toggle_hostels',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Assign permissions to roles
        // Admin gets all
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Bursar & Head of Finance get manage_hostel_fees
        $financeRoles = Role::whereIn('name', ['bursar', 'head_of_finance'])->get();
        foreach ($financeRoles as $role) {
            $role->givePermissionTo('manage_hostel_fees');
        }

        // Hostel Warden gets toggle_hostels
        $warden = Role::where('name', 'hostel_warden')->first();
        if ($warden) {
            $warden->givePermissionTo('toggle_hostels');
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'create_hostels',
            'manage_hostel_fees',
            'toggle_hostels',
        ];

        foreach ($permissions as $permission) {
            $p = Permission::where('name', $permission)->first();
            if ($p) {
                $p->delete();
            }
        }
    }
};
