<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->foreignUuid('exam_id')->nullable()->after('reference_id')->constrained('exams')->nullOnDelete();
        });

        // Backfill existing exam schedules into default Exam parent records
        $distinctPairs = DB::table('exam_schedules')
            ->select('session_id', 'semester_id')
            ->distinct()
            ->get();

        foreach ($distinctPairs as $pair) {
            $session = DB::table('academic_sessions')->where('id', $pair->session_id)->first();
            $semester = DB::table('semesters')->where('id', $pair->semester_id)->first();

            $examTitle = ($session?->name ?? 'Academic Session') . ' - ' . ($semester?->name ?? 'Semester') . ' Examinations';
            $examId = (string) Str::uuid();

            DB::table('exams')->insert([
                'id' => $examId,
                'reference_id' => 'EXM-' . strtoupper(substr(uniqid(), -6)),
                'title' => $examTitle,
                'session_id' => $pair->session_id,
                'semester_id' => $pair->semester_id,
                'exam_type' => null,
                'is_published' => true,
                'is_locked' => false,
                'docket_printing_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('exam_schedules')
                ->where('session_id', $pair->session_id)
                ->where('semester_id', $pair->semester_id)
                ->update(['exam_id' => $examId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_schedules', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->dropColumn('exam_id');
        });
    }
};
