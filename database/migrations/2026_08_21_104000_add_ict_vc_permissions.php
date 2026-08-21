<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create new permissions
        $newPermissions = [
            'edit_staff_profile',
            'assign_staff_roles',
            'reset_student_password',
        ];

        foreach ($newPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Setup ICT Staff role
        $ictStaff = Role::firstOrCreate(['name' => 'ict_staff']);
        $ictStaff->syncPermissions([
            'access_admin_dashboard',
            'view_staff',
            'edit_staff_profile',
            'assign_staff_roles',
            'view_students',
            'reset_student_password',
            'manage_users',
            'manage_system_settings',
            'view_system_status',
            'view_audit_logs',
            'view_recent_activities',
            'impersonate_users',
            'manage_bulk_communications',
        ]);

        // 3. Setup Vice Chancellor role (Executive Read-Only Oversight + Salaries & Hostels as requested)
        $vc = Role::firstOrCreate(['name' => 'vice_chancellor']);
        $vc->syncPermissions([
            'access_admin_dashboard',
            'view_staff',
            'view_students',
            'view_system_status',
            'view_audit_logs',
            'view_recent_activities',
            'view_global_analytics',
            'view_revenue_stats',
            'view_academic_stats',
            'view_admission_stats',
            'view_payments',
            'view_bursary_reports',
            'view_salaries',
            'manage_hostels',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destruct needed
    }
};
