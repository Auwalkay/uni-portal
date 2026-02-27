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
        // 1. Add Default Status to Courses
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_compulsory')->default(false)->after('units');
        });

        // 2. Add Max Units to Programmes
        Schema::table('programmes', function (Blueprint $table) {
            $table->integer('max_credit_units')->default(24)->after('type'); // Default 24
        });

        // 3. Create Pivot Table for Overrides
        Schema::create('course_programme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('programme_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_compulsory')->default(false);
            // $table->integer('level')->nullable(); // Optional: overrides level
            // $table->string('semester')->nullable(); // Optional: overrides semester

            $table->unique(['course_id', 'programme_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_programme');

        Schema::table('programmes', function (Blueprint $table) {
            $table->dropColumn('max_credit_units');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('is_compulsory');
        });
    }
};
