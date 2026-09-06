<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HodTimetablePermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'manage_timetables']);
        $hodRole = Role::firstOrCreate(['name' => 'hod']);

        if (!$hodRole->hasPermissionTo('manage_timetables')) {
            $hodRole->givePermissionTo($permission);
        }
    }
}
