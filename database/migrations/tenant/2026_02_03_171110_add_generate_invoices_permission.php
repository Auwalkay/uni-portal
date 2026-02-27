<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = \App\Models\Permission::firstOrCreate(['name' => 'generate_invoices']);

        $roles = ['admin', 'bursar', 'finance_officer'];

        foreach ($roles as $roleName) {
            $role = \App\Models\Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = \App\Models\Permission::where('name', 'generate_invoices')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
