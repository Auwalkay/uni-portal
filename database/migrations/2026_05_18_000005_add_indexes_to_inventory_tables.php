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
            $table->index('status');
        });

        Schema::table('inventory_complaints', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('inventory_complaints', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
