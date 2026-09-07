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
        if (!Schema::hasColumn('fee_types', 'is_per_course')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->boolean('is_per_course')->default(false)->after('is_one_time');
            });
        }

        if (!Schema::hasColumn('fee_configurations', 'semester_id')) {
            Schema::table('fee_configurations', function (Blueprint $table) {
                $table->foreignUuid('semester_id')->nullable()->after('session_id')->constrained('semesters')->onDelete('cascade');
            });
        }

        if (!Schema::hasColumn('fee_configurations', 'is_per_course')) {
            Schema::table('fee_configurations', function (Blueprint $table) {
                $table->boolean('is_per_course')->default(false)->after('is_compulsory');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('fee_types', 'is_per_course')) {
            Schema::table('fee_types', function (Blueprint $table) {
                $table->dropColumn('is_per_course');
            });
        }

        if (Schema::hasColumn('fee_configurations', 'is_per_course')) {
            Schema::table('fee_configurations', function (Blueprint $table) {
                $table->dropColumn('is_per_course');
            });
        }

        if (Schema::hasColumn('fee_configurations', 'semester_id')) {
            Schema::table('fee_configurations', function (Blueprint $table) {
                $table->dropForeign(['semester_id']);
                $table->dropColumn('semester_id');
            });
        }
    }
};
