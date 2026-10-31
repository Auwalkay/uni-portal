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
        Schema::table('course_registrations', function (Blueprint $table) {
            if (!$this->hasIndex('course_registrations', 'idx_cr_session_student')) {
                $table->index(['session_id', 'student_id'], 'idx_cr_session_student');
            }
            if (!$this->hasIndex('course_registrations', 'idx_cr_session_sem_student')) {
                $table->index(['session_id', 'semester_id', 'student_id'], 'idx_cr_session_sem_student');
            }
            if (!$this->hasIndex('course_registrations', 'idx_cr_session_sem_course')) {
                $table->index(['session_id', 'semester_id', 'course_id'], 'idx_cr_session_sem_course');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!$this->hasIndex('students', 'idx_students_program_id')) {
                $table->index('program_id', 'idx_students_program_id');
            }
            if (!$this->hasIndex('students', 'idx_students_level_status')) {
                $table->index(['current_level', 'status'], 'idx_students_level_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            if ($this->hasIndex('course_registrations', 'idx_cr_session_student')) {
                $table->dropIndex('idx_cr_session_student');
            }
            if ($this->hasIndex('course_registrations', 'idx_cr_session_sem_student')) {
                $table->dropIndex('idx_cr_session_sem_student');
            }
            if ($this->hasIndex('course_registrations', 'idx_cr_session_sem_course')) {
                $table->dropIndex('idx_cr_session_sem_course');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if ($this->hasIndex('students', 'idx_students_program_id')) {
                $table->dropIndex('idx_students_program_id');
            }
            if ($this->hasIndex('students', 'idx_students_level_status')) {
                $table->dropIndex('idx_students_level_status');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))->pluck('Key_name')->all();
            return in_array($indexName, $indexes, true);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
