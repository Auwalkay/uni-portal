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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_id')->unique();
            $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignUuid('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('level')->nullable();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue');
            $table->string('exam_type')->default('final');
            $table->integer('max_capacity')->default(100);
            $table->text('instructions')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_invigilators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_schedule_id')->constrained('exam_schedules')->cascadeOnDelete();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('role')->default('assistant'); // chief, assistant
            $table->string('status')->default('assigned'); // assigned, confirmed, absent
            $table->timestamps();
        });

        Schema::create('exam_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference_id')->unique();
            $table->foreignUuid('exam_schedule_id')->constrained('exam_schedules')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('invigilator_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('incident_type')->default('malpractice'); // malpractice, contraband, medical, impersonation, absenteeism, other
            $table->text('description');
            $table->string('status')->default('logged'); // logged, under_investigation, resolved, sanctioned
            $table->text('action_taken')->nullable();
            $table->foreignUuid('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_incidents');
        Schema::dropIfExists('exam_invigilators');
        Schema::dropIfExists('exam_schedules');
    }
};
