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
        Schema::table('hostel_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('hostel_blocks', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('description');
            }
        });

        Schema::table('hostel_floors', function (Blueprint $table) {
            if (!Schema::hasColumn('hostel_floors', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hostel_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('hostel_blocks', 'is_visible')) {
                $table->dropColumn('is_visible');
            }
        });

        Schema::table('hostel_floors', function (Blueprint $table) {
            if (Schema::hasColumn('hostel_floors', 'is_visible')) {
                $table->dropColumn('is_visible');
            }
        });
    }
};
