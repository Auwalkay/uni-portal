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
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignUuid('faculty_id')->nullable()->change();
            if (!Schema::hasColumn('departments', 'is_academic')) {
                $table->boolean('is_academic')->default(true)->after('faculty_id');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'unit_id')) {
                $table->foreignUuid('unit_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignUuid('faculty_id')->nullable(false)->change();
            $table->dropColumn('is_academic');
        });
    }
};
