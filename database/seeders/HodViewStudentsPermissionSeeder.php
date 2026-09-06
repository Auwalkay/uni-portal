<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HodViewStudentsPermissionSeeder extends Seeder
{
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'view_students']);
        $hodRole = Role::firstOrCreate(['name' => 'hod']);

        if (!$hodRole->hasPermissionTo('view_students')) {
            $hodRole->givePermissionTo($permission);
        }
    }
}
