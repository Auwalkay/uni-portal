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

        $pgPermissions = [
            'view_pg_applications',
            'review_pg_applications',
            'admit_pg_students',
        ];

        foreach ($pgPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 1. Super Admin and Admin roles get all permissions
        foreach (['admin', 'super_admin', 'super-admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($pgPermissions);
            }
        }

        // 2. Dean of SPGS gets full PG admission permissions
        $deanSpgs = Role::firstOrCreate(['name' => 'dean_spgs', 'guard_name' => 'web']);
        $deanSpgs->givePermissionTo($pgPermissions);

        // 3. Central Admission Director gets both UG and PG admissions
        $admissionDirector = Role::where('name', 'admission_director')->first();
        if ($admissionDirector) {
            $admissionDirector->givePermissionTo($pgPermissions);
        }

        // 4. Create dedicated PG Admissions Officer role
        $pgOfficer = Role::firstOrCreate(['name' => 'pg_admissions_officer', 'guard_name' => 'web']);
        $pgOfficer->givePermissionTo([
            'access_admin_dashboard',
            'view_pg_applications',
            'review_pg_applications',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $pgPermissions = [
            'view_pg_applications',
            'review_pg_applications',
            'admit_pg_students',
        ];

        foreach ($pgPermissions as $perm) {
            Permission::where('name', $perm)->where('guard_name', 'web')->delete();
        }

        Role::where('name', 'pg_admissions_officer')->delete();
    }
};

