<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create permission 'edit_student_name_email'
        $permissionNameEmail = Permission::firstOrCreate(['name' => 'edit_student_name_email']);

        // Give it to admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissionNameEmail);
        }

        // 2. Create 'hostel_warden' role
        $warden = Role::firstOrCreate(['name' => 'hostel_warden']);
        
        // Find existing permissions to assign to warden
        $permissions = [
            'access_admin_dashboard',
            'view_students',
            'manage_hostels',
            'manage_hostel_bookings',
        ];

        foreach ($permissions as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
                $warden->givePermissionTo($perm);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionNameEmail = Permission::where('name', 'edit_student_name_email')->first();
        if ($permissionNameEmail) {
            $permissionNameEmail->delete();
        }

        $warden = Role::where('name', 'hostel_warden')->first();
        if ($warden) {
            $warden->delete();
        }
    }
};
