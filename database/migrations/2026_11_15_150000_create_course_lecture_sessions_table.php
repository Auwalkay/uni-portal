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
        Schema::create('course_lecture_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->date('lecture_date');
            $table->json('department_ids')->nullable(); // null or empty = All Departments
            $table->json('department_names')->nullable(); // Cached department names for fast display
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['course_id', 'session_id', 'lecture_date'], 'idx_cls_course_session_date');
        });

        Schema::table('student_course_attendances', function (Blueprint $table) {
            $table->foreignUuid('course_lecture_session_id')->nullable()->after('session_id')->constrained('course_lecture_sessions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_course_attendances', function (Blueprint $table) {
            $table->dropForeign(['course_lecture_session_id']);
            $table->dropColumn('course_lecture_session_id');
        });

        Schema::dropIfExists('course_lecture_sessions');
    }
};
