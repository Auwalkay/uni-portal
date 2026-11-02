<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add course_type to courses table for PG/UG course delivery & assessment modes
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'course_type')) {
                $table->string('course_type')->default('lecture_exam')->after('semester');
                $table->index(['level', 'course_type'], 'idx_courses_level_type');
            }
        });

        // 2. Add billing_model and min_graduation_cgpa to programmes table
        Schema::table('programmes', function (Blueprint $table) {
            if (! Schema::hasColumn('programmes', 'billing_model')) {
                $table->string('billing_model')->default('composite_term')->after('matric_format');
            }
            if (! Schema::hasColumn('programmes', 'min_graduation_cgpa')) {
                $table->decimal('min_graduation_cgpa', 3, 2)->nullable()->after('billing_model');
            }
        });

        // Backfill min_graduation_cgpa for existing PG programmes
        DB::table('programmes')
            ->where('academic_stream', 'PG')
            ->whereNull('min_graduation_cgpa')
            ->update(['min_graduation_cgpa' => 3.00]);

        // 3. Add milestone fee support to fee_configurations table
        Schema::table('fee_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_configurations', 'is_milestone_fee')) {
                $table->boolean('is_milestone_fee')->default(false)->after('is_per_course');
            }
            if (! Schema::hasColumn('fee_configurations', 'milestone_type')) {
                $table->string('milestone_type')->nullable()->after('is_milestone_fee');
                $table->index(['session_id', 'is_milestone_fee', 'milestone_type'], 'idx_fee_configs_milestone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('fee_configurations', 'milestone_type')) {
                $table->dropIndex('idx_fee_configs_milestone');
                $table->dropColumn(['is_milestone_fee', 'milestone_type']);
            }
        });

        Schema::table('programmes', function (Blueprint $table) {
            $columns = array_filter(
                ['billing_model', 'min_graduation_cgpa'],
                fn ($col) => Schema::hasColumn('programmes', $col)
            );
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'course_type')) {
                $table->dropIndex('idx_courses_level_type');
                $table->dropColumn('course_type');
            }
        });
    }
};

