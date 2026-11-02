<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PostgraduateRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_postgraduate_school',
            'view_pg_dashboard',
            'manage_pg_supervisors',
            'view_pg_supervisors',
            'supervise_pg_students',
            'upload_pg_milestones',
            'review_pg_milestones',
            'score_pg_seminar_defense',
            'issue_pg_plagiarism_clearance',
            'review_pg_logbooks',
            'endorse_pg_apr',
            'approve_pg_petitions',
            'manage_pg_examiners',
            'score_viva_defense',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 1. Super Admin & Admin Roles
        foreach (['admin', 'super_admin', 'super-admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        // 2. Dean of SPGS Role
        $deanSpgsRole = Role::firstOrCreate(['name' => 'dean_spgs', 'guard_name' => 'web']);
        $deanSpgsRole->givePermissionTo($permissions);

        // 3. Faculty Dean Role
        $deanRole = Role::where('name', 'dean')->first();
        if ($deanRole) {
            $deanRole->givePermissionTo([
                'view_pg_dashboard',
                'view_pg_supervisors',
                'endorse_pg_apr',
                'review_pg_milestones',
            ]);
        }

        // 4. HOD & DPGC Chair Roles
        foreach (['hod', 'dpgc_chair'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo([
                'view_pg_dashboard',
                'manage_pg_supervisors',
                'view_pg_supervisors',
                'upload_pg_milestones',
                'review_pg_milestones',
                'score_pg_seminar_defense',
                'issue_pg_plagiarism_clearance',
                'endorse_pg_apr',
                'manage_pg_examiners',
            ]);
        }

        // 5. Academic Staff / Lecturers / Supervisors
        foreach (['lecturer', 'staff', 'pg_supervisor'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo([
                'supervise_pg_students',
                'upload_pg_milestones',
                'review_pg_milestones',
                'score_pg_seminar_defense',
                'issue_pg_plagiarism_clearance',
                'review_pg_logbooks',
                'endorse_pg_apr',
            ]);
        }
    }
}

