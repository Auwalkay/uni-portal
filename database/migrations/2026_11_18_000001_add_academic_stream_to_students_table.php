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
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'academic_stream')) {
                $table->string('academic_stream', 10)->default('UG')->after('program_id');
            }
            $table->index(['academic_stream', 'status'], 'idx_students_stream_status');
            $table->index(['academic_stream', 'current_level'], 'idx_students_stream_level');
        });

        // Backfill existing postgraduate students
        $pgProgramIds = DB::table('programmes')
            ->where('academic_stream', 'PG')
            ->pluck('id')
            ->all();

        DB::table('students')
            ->where(function ($q) use ($pgProgramIds) {
                $q->where('current_level', '>=', 700)
                    ->orWhereRaw("LOWER(COALESCE(entry_mode, '')) = 'postgraduate'");
                if (! empty($pgProgramIds)) {
                    $q->orWhereIn('program_id', $pgProgramIds);
                }
            })
            ->update(['academic_stream' => 'PG']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_stream_status');
            $table->dropIndex('idx_students_stream_level');
            if (Schema::hasColumn('students', 'academic_stream')) {
                $table->dropColumn('academic_stream');
            }
        });
    }
};
