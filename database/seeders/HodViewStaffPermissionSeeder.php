<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HodViewStaffPermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'view_staff']);
        $hodRole = Role::firstOrCreate(['name' => 'hod']);

        if (!$hodRole->hasPermissionTo('view_staff')) {
            $hodRole->givePermissionTo($permission);
        }
    }
}
