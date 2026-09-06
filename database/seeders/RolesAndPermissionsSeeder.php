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

        // 1. All Application Permissions
        $permissions = [
            // General & Portal Access
            'access_admin_dashboard',
            'access_student_portal',
            'access_applicant_portal',
            'access_staff_portal',
            
            // Academic Management (Courses & Structure)
            'view_faculties', 'manage_faculties',
            'view_departments', 'manage_departments',
            'view_programmes', 'manage_programmes',
            'view_courses', 'manage_courses',
            'assign_coordinators',
            'manage_academic_sessions',
            'manage_timetables',
            'manage_exams',
            
            // Result Management
            'view_results',
            'enter_results',
            'edit_results',
            'approve_results',
            'publish_results',
            'manage_results', // Global Override
            
            // Student & Admissions
            'view_applications',
            'review_applications',
            'admit_students',
            'view_students',
            'create_students',
            'edit_students',
            'edit_student_name_email',
            'delete_students',
            'import_students',
            'manage_users', // Global student search/access
            
            // Staff & HR
            'view_staff',
            'create_staff',
            'edit_staff',
            'edit_staff_profile',
            'assign_staff_roles',
            'delete_staff',
            'manage_staff', // Global staff management
            'view_salaries',
            'manage_salaries',
            'run_payroll',
            'view_attendance',
            'manage_attendance',
            'reset_student_password',
            'fix_course_registration',
            
            // Finance & Payments
            'view_payments',
            'verify_payments',
            'manage_payments',
            'manual_payment_override',
            'create_invoices',
            'cancel_invoices',
            'edit_invoices',
            'manage_scholarships',
            'view_expenses',
            'create_expenses',
            'approve_expenses',
            'request_expenses_for_others',
            'view_bursary_reports',
            
            // Infrastructure & Utilities
            'manage_hostels',
            'manage_hostel_bookings',
            'view_hostel_bookings',
            'view_male_hostel_bookings',
            'view_female_hostel_bookings',
            'create_hostels',
            'manage_hostel_fees',
            'toggle_hostels',
            'manage_visitors',
            'view_audit_logs',
            'manage_system_settings',
            'manage_support',

            // Dashboard & Analytics
            'view_global_analytics',
            'view_revenue_stats',
            'view_academic_stats',
            'view_admission_stats',
            'view_recent_activities',
            'view_system_status',
            
            // Advanced & Compliance
            'impersonate_users',
            'export_pii_data',
            'bypass_registration_limits',
            'override_prerequisites',
            'manage_student_clearance',
            'issue_official_transcripts',
            'manage_bulk_communications',
            'perform_student_registration',
            'manage_student_registrations',
            
            // Inventory Management
            'view_inventory',
            'manage_inventory',
            'create_inventory_items',
            'edit_inventory_items',
            'delete_inventory_items',
            'restock_inventory_items',
            'create_inventory_requisitions',
            'approve_inventory_requisitions',
            'assign_inventory',
            'view_inventory_requisitions',
            'manage_inventory_requisitions',
            'view_inventory_assignments',
            'manage_inventory_assignments',
            'view_inventory_categories',
            'manage_inventory_categories',
            'view_inventory_audit_logs',
            'manage_inventory_complaints',
            
            // Personal
            'view_own_payslips',

            // Library Management
            'view_library',
            'manage_library_books',
            'manage_library_borrows',
            'request_library_book',

            // Sickbay Management
            'view_sickbay_portal',
            'register_walk_in',
            'write_sickbay_medical_logs',
            'manage_observation_beds',
            'manage_sickbay_inventory',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Define Roles and Assign Permissions Additively (givePermissionTo preserves existing permissions)

        // Super Admin & Admin gets ALL permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Helper function for non-destructive additive permission assignment
        $assign = function(string $roleName, array $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->givePermissionTo($perms);
        };

        // Librarian
        $assign('librarian', [
            'access_admin_dashboard',
            'view_library',
            'manage_library_books',
            'manage_library_borrows',
            'view_expenses',
            'create_expenses',
        ]);

        // Sickbay Nurse
        $assign('sickbay_nurse', [
            'access_admin_dashboard',
            'view_sickbay_portal',
            'register_walk_in',
            'write_sickbay_medical_logs',
            'manage_observation_beds',
            'manage_sickbay_inventory',
            'view_expenses',
            'create_expenses',
        ]);

        // Hostel Supervisors & Viewer
        $assign('male_hostel_supervisor', [
            'access_admin_dashboard',
            'view_hostel_bookings',
            'view_male_hostel_bookings',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('female_hostel_supervisor', [
            'access_admin_dashboard',
            'view_hostel_bookings',
            'view_female_hostel_bookings',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('hostel_viewer', [
            'access_admin_dashboard',
            'view_hostel_bookings',
        ]);

        // Academic Roles
        $assign('registrar', [
            'access_admin_dashboard',
            'manage_courses', 
            'assign_coordinators', 
            'approve_results', 
            'view_results', 
            'view_applications', 
            'view_payments', 
            'manage_staff',
            'manage_academic_sessions',
            'manage_hostels',
            'view_hostel_bookings',
            'view_attendance',
            'manage_attendance',
            'perform_student_registration',
            'manage_student_registrations',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('hr_manager', [
            'access_admin_dashboard',
            'view_staff',
            'manage_staff',
            'view_attendance',
            'manage_attendance',
            'view_salaries',
            'run_payroll',
            'view_expenses',
            'create_expenses',
            'request_expenses_for_others',
        ]);

        $assign('dean', [
            'access_admin_dashboard',
            'approve_results',
            'view_results',
            'manage_courses',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('hod', [
            'access_admin_dashboard',
            'approve_results',
            'view_results',
            'manage_courses',
            'assign_coordinators',
            'perform_student_registration',
            'manage_student_registrations',
            'view_staff',
            'manage_timetables',
            'view_students',
            'view_expenses',
            'create_expenses',
            'request_expenses_for_others',
        ]);

        $assign('course_coordinator', [
            'access_admin_dashboard',
            'view_results',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('lecturer', [
            'access_admin_dashboard',
            'view_results',
            'view_expenses',
            'create_expenses',
        ]);

        // Admissions Roles
        $assign('admissions_manager', [
            'access_admin_dashboard',
            'admit_students',
            'review_applications',
            'view_applications',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('admissions_officer', [
            'access_admin_dashboard',
            'review_applications',
            'view_applications',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('admissions_clerk', [
            'access_admin_dashboard',
            'view_applications',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('admission_director', [
            'access_admin_dashboard',
            'view_applications',
            'review_applications',
            'admit_students',
            'view_students',
            'edit_students',
            'view_expenses',
            'create_expenses',
        ]);

        // Finance & Inventory Roles
        $assign('bursar', [
            'access_admin_dashboard',
            'manage_payments',
            'verify_payments',
            'view_payments',
            'manual_payment_override',
            'view_bursary_reports',
            'manage_hostel_fees',
            'edit_invoices',
            'view_expenses',
            'create_expenses',
            'request_expenses_for_others',
            'view_inventory',
            'manage_inventory',
            'create_inventory_items',
            'edit_inventory_items',
            'delete_inventory_items',
            'restock_inventory_items',
            'create_inventory_requisitions',
            'approve_inventory_requisitions',
            'view_inventory_requisitions',
            'manage_inventory_requisitions',
            'view_inventory_assignments',
            'manage_inventory_assignments',
            'view_inventory_categories',
            'manage_inventory_categories',
            'view_inventory_audit_logs',
        ]);

        $assign('head_of_finance', [
            'access_admin_dashboard',
            'manage_payments',
            'verify_payments',
            'view_payments',
            'manual_payment_override',
            'view_bursary_reports',
            'manage_hostel_fees',
            'edit_invoices',
            'view_expenses',
            'create_expenses',
            'request_expenses_for_others',
        ]);

        $assign('finance_officer', [
            'access_admin_dashboard',
            'verify_payments',
            'view_payments',
            'edit_invoices',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('finance_clerk', [
            'access_admin_dashboard',
            'view_payments',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('store_officer', [
            'access_admin_dashboard',
            'view_inventory',
            'manage_inventory',
            'create_inventory_items',
            'edit_inventory_items',
            'delete_inventory_items',
            'restock_inventory_items',
            'create_inventory_requisitions',
            'approve_inventory_requisitions',
            'view_inventory_requisitions',
            'manage_inventory_requisitions',
            'view_inventory_assignments',
            'manage_inventory_assignments',
            'view_inventory_categories',
            'manage_inventory_categories',
            'view_inventory_audit_logs',
            'view_expenses',
            'create_expenses',
        ]);

        // Hostel Roles
        $assign('hostel_warden', [
            'access_admin_dashboard',
            'view_students',
            'manage_hostels',
            'manage_hostel_bookings',
            'toggle_hostels',
            'view_expenses',
            'create_expenses',
        ]);

        // Front Desk & ICT & Executive
        $assign('receptionist', [
            'access_admin_dashboard',
            'manage_visitors',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('ict_staff', [
            'access_admin_dashboard',
            'view_staff',
            'edit_staff_profile',
            'assign_staff_roles',
            'view_students',
            'reset_student_password',
            'fix_course_registration',
            'manage_users',
            'manage_system_settings',
            'manage_support',
            'view_system_status',
            'view_audit_logs',
            'view_recent_activities',
            'impersonate_users',
            'manage_bulk_communications',
            'view_expenses',
            'create_expenses',
        ]);

        $assign('vice_chancellor', [
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
            'view_expenses',
            'create_expenses',
        ]);

        // Core Staff & Non-Academic Staff
        $staffRoles = [
            'staff',
            'cleaner',
            'driver',
            'carpenter',
            'janitor',
            'security_officer',
            'maintenance_worker',
        ];

        foreach ($staffRoles as $roleName) {
            $assign($roleName, [
                'access_staff_portal',
                'view_own_payslips',
                'view_library',
                'request_library_book',
                'view_sickbay_portal',
                'view_expenses',
                'create_expenses',
            ]);
        }

        // Student & Applicant
        $assign('student', [
            'access_student_portal',
            'view_library',
            'request_library_book',
            'view_sickbay_portal',
        ]);

        $assign('applicant', [
            'access_applicant_portal',
        ]);
    }
}
