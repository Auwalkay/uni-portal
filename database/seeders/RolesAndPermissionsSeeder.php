<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'view_applicants',
            'review_applications',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and assign created permissions
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->permissions()->sync(Permission::whereIn('name', $permissions)->pluck('id'));

        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->permissions()->sync(Permission::where('name', 'view_applicants')->pluck('id'));

        Role::firstOrCreate(['name' => 'applicant']);
        Role::firstOrCreate(['name' => 'student']);
    }
}
