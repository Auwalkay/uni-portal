<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('late_fine_waived')->default(false)->after('late_fine_applied');
            $table->timestamp('late_fine_waived_at')->nullable()->after('late_fine_waived');
            $table->foreignUuid('late_fine_waived_by')->nullable()->after('late_fine_waived_at')->constrained('users')->nullOnDelete();
            $table->string('late_fine_waived_reason', 500)->nullable()->after('late_fine_waived_by');

            $table->index('late_fine_waived');
        });

        // Ensure Spatie permissions cache is cleared
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permission and assign to authorized roles
        $permission = Permission::firstOrCreate(['name' => 'waive_late_charges', 'guard_name' => 'web']);

        $roles = ['super_admin', 'admin', 'bursar'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['late_fine_waived_by']);
            $table->dropIndex(['late_fine_waived']);
            $table->dropColumn([
                'late_fine_waived',
                'late_fine_waived_at',
                'late_fine_waived_by',
                'late_fine_waived_reason',
            ]);
        });

        $permission = Permission::where('name', 'waive_late_charges')->first();
        if ($permission) {
            $permission->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
