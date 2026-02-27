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
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Define Roles and Assign Permissions

        // --- ADMIN (Super User) ---
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // --- ACADEMIC ROLES ---
        $registrar = Role::firstOrCreate(['name' => 'registrar', 'guard_name' => 'web']);
        $registrar->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['manage_courses', 'assign_coordinators', 'approve_results', 'view_results', 'view_applications', 'view_payments', 'manage_staff'])->get());

        $dean = Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);
        $dean->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['approve_results', 'view_results', 'manage_courses'])->get());

        $hod = Role::firstOrCreate(['name' => 'hod', 'guard_name' => 'web']);
        $hod->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['approve_results', 'view_results', 'manage_courses', 'assign_coordinators'])->get());

        $courseCoordinator = Role::firstOrCreate(['name' => 'course_coordinator', 'guard_name' => 'web']);
        $courseCoordinator->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['manage_results', 'view_results'])->get());

        $lecturer = Role::firstOrCreate(['name' => 'lecturer', 'guard_name' => 'web']);
        $lecturer->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['manage_results', 'view_results'])->get());

        // --- ADMISSIONS ROLES ---
        $admissionsManager = Role::firstOrCreate(['name' => 'admissions_manager', 'guard_name' => 'web']);
        $admissionsManager->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['admit_students', 'review_applications', 'view_applications'])->get());

        $admissionsOfficer = Role::firstOrCreate(['name' => 'admissions_officer', 'guard_name' => 'web']);
        $admissionsOfficer->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['review_applications', 'view_applications'])->get());

        $admissionsClerk = Role::firstOrCreate(['name' => 'admissions_clerk', 'guard_name' => 'web']);
        $admissionsClerk->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['view_applications'])->get());

        // --- FINANCE ROLES ---
        $bursar = Role::firstOrCreate(['name' => 'bursar', 'guard_name' => 'web']);
        $bursar->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['manage_payments', 'verify_payments', 'view_payments'])->get());

        $financeOfficer = Role::firstOrCreate(['name' => 'finance_officer', 'guard_name' => 'web']);
        $financeOfficer->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['verify_payments', 'view_payments'])->get());

        $financeClerk = Role::firstOrCreate(['name' => 'finance_clerk', 'guard_name' => 'web']);
        $financeClerk->syncPermissions(Permission::where('guard_name', 'web')->whereIn('name', ['view_payments'])->get());

        // --- CORE ROLES ---
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);
    }
}
