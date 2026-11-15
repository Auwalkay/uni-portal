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
        Schema::create('student_course_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_registration_id')->constrained('course_registrations')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20)->default('present');
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Unique constraint: one attendance log per student course registration per date
            $table->unique(['course_registration_id', 'attendance_date'], 'uq_course_reg_att_date');

            // High-throughput performance indexes for 5M+ student scale
            $table->index(['course_registration_id', 'status'], 'idx_sca_reg_status');
            $table->index(['student_id', 'session_id'], 'idx_sca_student_session');
            $table->index(['course_id', 'session_id', 'attendance_date'], 'idx_sca_course_session_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_course_attendances');
    }
};
