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
            'delete_students',
            'import_students',
            'manage_users', // Global student search/access
            
            // Staff & HR
            'view_staff',
            'create_staff',
            'edit_staff',
            'delete_staff',
            'manage_staff', // Global staff management
            'view_salaries',
            'manage_salaries',
            'run_payroll',
            'view_attendance',
            'manage_attendance',
            
            // Finance & Payments
            'view_payments',
            'verify_payments',
            'manage_payments',
            'manual_payment_override',
            'create_invoices',
            'cancel_invoices',
            'manage_scholarships',
            'view_expenses',
            'create_expenses',
            'approve_expenses',
            'view_bursary_reports',
            
            // Infrastructure & Utilities
            'manage_hostels',
            'manage_hostel_bookings',
            'manage_visitors',
            'view_audit_logs',
            'manage_system_settings',
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
            
            // Personal
            'view_own_payslips',
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
        $registrar->syncPermissions([
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
            'view_attendance',
            'manage_attendance'
        ]);
        
        $hrManager = Role::firstOrCreate(['name' => 'hr_manager']);
        $hrManager->syncPermissions([
            'access_admin_dashboard',
            'view_staff',
            'manage_staff',
            'view_attendance',
            'manage_attendance',
            'view_salaries',
            'run_payroll'
        ]);

        $dean = Role::firstOrCreate(['name' => 'dean']);
        $dean->syncPermissions(['access_admin_dashboard', 'approve_results', 'view_results', 'manage_courses']);

        $hod = Role::firstOrCreate(['name' => 'hod']);
        $hod->syncPermissions(['access_admin_dashboard', 'approve_results', 'view_results', 'manage_courses', 'assign_coordinators']);

        $courseCoordinator = Role::firstOrCreate(['name' => 'course_coordinator']);
        $courseCoordinator->syncPermissions(['access_admin_dashboard', 'view_results']);

        $lecturer = Role::firstOrCreate(['name' => 'lecturer']);
        $lecturer->syncPermissions(['access_admin_dashboard', 'view_results']);

        // --- ADMISSIONS ROLES ---
        $admissionsManager = Role::firstOrCreate(['name' => 'admissions_manager']);
        $admissionsManager->syncPermissions(['access_admin_dashboard', 'admit_students', 'review_applications', 'view_applications']);

        $admissionsOfficer = Role::firstOrCreate(['name' => 'admissions_officer']);
        $admissionsOfficer->syncPermissions(['access_admin_dashboard', 'review_applications', 'view_applications']);

        $admissionsClerk = Role::firstOrCreate(['name' => 'admissions_clerk']);
        $admissionsClerk->syncPermissions(['access_admin_dashboard', 'view_applications']);

        // --- FINANCE ROLES ---
        $bursar = Role::firstOrCreate(['name' => 'bursar']);
        $bursar->syncPermissions(['access_admin_dashboard', 'manage_payments', 'verify_payments', 'view_payments', 'manual_payment_override', 'view_bursary_reports']);

        $headOfFinance = Role::firstOrCreate(['name' => 'head_of_finance']);
        $headOfFinance->syncPermissions(['access_admin_dashboard', 'manage_payments', 'verify_payments', 'view_payments', 'manual_payment_override', 'view_bursary_reports']);

        $financeOfficer = Role::firstOrCreate(['name' => 'finance_officer']);
        $financeOfficer->syncPermissions(['access_admin_dashboard', 'verify_payments', 'view_payments']);

        $financeClerk = Role::firstOrCreate(['name' => 'finance_clerk']);
        $financeClerk->syncPermissions(['access_admin_dashboard', 'view_payments']);

        // --- FRONT DESK ---
        $receptionist = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionist->syncPermissions(['access_admin_dashboard', 'manage_visitors']);

        // --- CORE ROLES ---
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions(['access_staff_portal', 'view_own_payslips']);

        // --- NON-ACADEMIC SPECIFIC ROLES ---
        $nonAcademicRoles = [
            'cleaner',
            'driver',
            'carpenter',
            'janitor',
            'security_officer',
            'maintenance_worker',
        ];

        foreach ($nonAcademicRoles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions(['access_staff_portal', 'view_own_payslips']);
        }

        $student = Role::firstOrCreate(['name' => 'student']);
        $student->syncPermissions(['access_student_portal']);

        $applicant = Role::firstOrCreate(['name' => 'applicant']);
        $applicant->syncPermissions(['access_applicant_portal']);
    }
}
