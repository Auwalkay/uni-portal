<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_assignments', 'staff_id')) {
                $table->foreignUuid('staff_id')
                    ->nullable()
                    ->after('building_id')
                    ->constrained('staff')
                    ->nullOnDelete();

                $table->index(['staff_id', 'status']);
                $table->index(['assignable_type', 'status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_assignments', 'staff_id')) {
                $table->dropIndex(['staff_id', 'status']);
                $table->dropIndex(['assignable_type', 'status']);
                $table->dropForeign(['staff_id']);
                $table->dropColumn('staff_id');
            }
        });
    }
};
