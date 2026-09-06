<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddEditInvoicesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'edit_invoices']);

        $admin = Role::where('name', 'admin')->first();
        if ($admin && !$admin->hasPermissionTo('edit_invoices')) {
            $admin->givePermissionTo($perm);
        }
    }
}
