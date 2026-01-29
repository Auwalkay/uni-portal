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
        Schema::table('faculties', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
