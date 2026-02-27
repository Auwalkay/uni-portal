<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\CentralUser;

class CentralRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Central Roles
        $roles = [
            'super_admin',
            'nbte_user',
            'auditor',
            'support',
            'admin',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'central'
            ]);
        }

        // 2. Assign Super Admin to the existing central admin user
        $admin = CentralUser::where('email', 'admin@nbte.gov.ng')->first();
        if ($admin) {
            $admin->assignRole('super_admin');
        }
    }
}
