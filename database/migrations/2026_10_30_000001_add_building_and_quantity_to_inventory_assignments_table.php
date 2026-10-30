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
            if (!Schema::hasColumn('inventory_assignments', 'building_id')) {
                $table->foreignUuid('building_id')
                    ->nullable()
                    ->after('inventory_item_id')
                    ->constrained('buildings')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('inventory_assignments', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('building_id');
            }

            if (!Schema::hasColumn('inventory_assignments', 'location_details')) {
                $table->string('location_details')->nullable()->after('quantity');
            }

            $table->index(['building_id', 'status']);
            $table->index(['inventory_item_id', 'status']);
            $table->index('assigned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            $table->dropIndex(['building_id', 'status']);
            $table->dropIndex(['inventory_item_id', 'status']);
            $table->dropIndex(['assigned_at']);

            if (Schema::hasColumn('inventory_assignments', 'building_id')) {
                $table->dropForeign(['building_id']);
                $table->dropColumn('building_id');
            }

            if (Schema::hasColumn('inventory_assignments', 'quantity')) {
                $table->dropColumn('quantity');
            }

            if (Schema::hasColumn('inventory_assignments', 'location_details')) {
                $table->dropColumn('location_details');
            }
        });
    }
};
