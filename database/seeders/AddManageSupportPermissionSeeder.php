<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddManageSupportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'manage_support']);

        $admin = Role::where('name', 'admin')->first();
        if ($admin && !$admin->hasPermissionTo('manage_support')) {
            $admin->givePermissionTo($perm);
        }
    }
}
