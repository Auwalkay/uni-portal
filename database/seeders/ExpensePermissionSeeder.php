<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class ExpensePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create the new permission
        $permission = Permission::firstOrCreate(['name' => 'request_expenses_for_others']);

        // 2. Roles that can request on behalf of others
        $rolesForOthers = [
            'admin',
            'bursar',
            'head_of_finance',
            'hod',
            'hr_manager',
        ];

        foreach ($rolesForOthers as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('request_expenses_for_others');
            }
        }

        // 3. Ensure all staff and faculty roles have permissions to view and create their own expenses
        $staffRoles = [
            'admin',
            'librarian',
            'sickbay_nurse',
            'registrar',
            'hr_manager',
            'dean',
            'hod',
            'course_coordinator',
            'lecturer',
            'admissions_manager',
            'admissions_officer',
            'admissions_clerk',
            'admission_director',
            'bursar',
            'head_of_finance',
            'finance_officer',
            'finance_clerk',
            'receptionist',
            'staff',
            'cleaner',
            'driver',
            'carpenter',
            'janitor',
            'security_officer',
            'maintenance_worker',
        ];

        foreach ($staffRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(['view_expenses', 'create_expenses']);
            }
        }
    }
}
