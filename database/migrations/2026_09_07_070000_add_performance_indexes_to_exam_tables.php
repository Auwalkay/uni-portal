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
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->index(['session_id', 'semester_id'], 'idx_exam_sch_session_semester');
            $table->index(['exam_date', 'start_time', 'end_time'], 'idx_exam_sch_date_time');
            $table->index(['venue', 'exam_date'], 'idx_exam_sch_venue_date');
        });

        Schema::table('exam_invigilators', function (Blueprint $table) {
            $table->index(['exam_schedule_id', 'staff_id'], 'idx_exam_inv_schedule_staff');
        });

        Schema::table('exam_attendances', function (Blueprint $table) {
            $table->index(['student_id', 'exam_schedule_id'], 'idx_exam_att_student_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attendances', function (Blueprint $table) {
            $table->dropIndex('idx_exam_att_student_schedule');
        });

        Schema::table('exam_invigilators', function (Blueprint $table) {
            $table->dropIndex('idx_exam_inv_schedule_staff');
        });

        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_exam_sch_venue_date');
            $table->dropIndex('idx_exam_sch_date_time');
            $table->dropIndex('idx_exam_sch_session_semester');
        });
    }
};
