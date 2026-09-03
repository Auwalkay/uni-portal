<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventoryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view_inventory',
            'manage_inventory',
            'create_inventory_items',
            'edit_inventory_items',
            'delete_inventory_items',
            'restock_inventory_items',
            'create_inventory_requisitions',
            'approve_inventory_requisitions',
            'view_inventory_requisitions',
            'manage_inventory_requisitions',
            'view_inventory_assignments',
            'manage_inventory_assignments',
            'view_inventory_categories',
            'manage_inventory_categories',
            'view_inventory_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign to Super Admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Assign to Admin & Bursar & Store Officer
        $rolesToAssign = Role::whereIn('name', ['admin', 'bursar', 'store_officer'])->get();
        foreach ($rolesToAssign as $role) {
            $role->givePermissionTo($permissions);
        }
    }
}
