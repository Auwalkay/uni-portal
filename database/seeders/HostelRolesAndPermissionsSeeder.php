<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class HostelRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_hostel_bookings',
            'view_male_hostel_bookings',
            'view_female_hostel_bookings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super Admin gets all permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        foreach ($permissions as $p) {
            $admin->givePermissionTo($p);
        }

        $registrar = Role::firstOrCreate(['name' => 'registrar']);
        $registrar->givePermissionTo('view_hostel_bookings');

        // Male Hostel Supervisor
        $maleHostelSupervisor = Role::firstOrCreate(['name' => 'male_hostel_supervisor']);
        $maleHostelSupervisor->syncPermissions([
            'access_admin_dashboard',
            'view_hostel_bookings',
            'view_male_hostel_bookings',
        ]);

        // Female Hostel Supervisor
        $femaleHostelSupervisor = Role::firstOrCreate(['name' => 'female_hostel_supervisor']);
        $femaleHostelSupervisor->syncPermissions([
            'access_admin_dashboard',
            'view_hostel_bookings',
            'view_female_hostel_bookings',
        ]);

        // Hostel Viewer (All hostels view-only)
        $hostelViewer = Role::firstOrCreate(['name' => 'hostel_viewer']);
        $hostelViewer->syncPermissions([
            'access_admin_dashboard',
            'view_hostel_bookings',
        ]);
    }
}
