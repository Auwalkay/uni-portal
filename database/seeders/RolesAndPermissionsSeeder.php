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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions
        $permissions = [
            // Results
            'manage_results',
            'approve_results',
            'view_results',
            // Payments
            'manage_payments',
            'verify_payments',
            'view_payments',
            // Admissions
            'admit_students',
            'review_applications',
            'view_applications',
            // Academics
            'manage_courses',
            'assign_coordinators',
            'manage_settings',
            'manage_staff',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Define Roles and Assign Permissions

        // --- ADMIN (Super User) ---
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // --- ACADEMIC ROLES ---
        $registrar = Role::firstOrCreate(['name' => 'registrar']);
        $registrar->syncPermissions(['manage_courses', 'assign_coordinators', 'approve_results', 'view_results', 'view_applications', 'view_payments', 'manage_staff']);

        $dean = Role::firstOrCreate(['name' => 'dean']);
        $dean->syncPermissions(['approve_results', 'view_results', 'manage_courses']);

        $hod = Role::firstOrCreate(['name' => 'hod']);
        $hod->syncPermissions(['approve_results', 'view_results', 'manage_courses', 'assign_coordinators']);

        $courseCoordinator = Role::firstOrCreate(['name' => 'course_coordinator']);
        $courseCoordinator->syncPermissions(['manage_results', 'view_results']);

        $lecturer = Role::firstOrCreate(['name' => 'lecturer']);
        $lecturer->syncPermissions(['manage_results', 'view_results']);

        // --- ADMISSIONS ROLES ---
        $admissionsManager = Role::firstOrCreate(['name' => 'admissions_manager']);
        $admissionsManager->syncPermissions(['admit_students', 'review_applications', 'view_applications']);

        $admissionsOfficer = Role::firstOrCreate(['name' => 'admissions_officer']);
        $admissionsOfficer->syncPermissions(['review_applications', 'view_applications']);

        $admissionsClerk = Role::firstOrCreate(['name' => 'admissions_clerk']);
        $admissionsClerk->syncPermissions(['view_applications']);

        // --- FINANCE ROLES ---
        $bursar = Role::firstOrCreate(['name' => 'bursar']);
        $bursar->syncPermissions(['manage_payments', 'verify_payments', 'view_payments']);

        $financeOfficer = Role::firstOrCreate(['name' => 'finance_officer']);
        $financeOfficer->syncPermissions(['verify_payments', 'view_payments']);

        $financeClerk = Role::firstOrCreate(['name' => 'finance_clerk']);
        $financeClerk->syncPermissions(['view_payments']);

        // --- CORE ROLES ---
        Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'applicant']);
    }
}
