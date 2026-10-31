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
        // 1. Update academic_sessions table
        Schema::table('academic_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_sessions', 'academic_stream')) {
                $table->string('academic_stream')->default('UG')->after('type'); // 'UG', 'PG'
            }
            if (!Schema::hasColumn('academic_sessions', 'calendar_type')) {
                $table->string('calendar_type')->default('semester')->after('academic_stream'); // 'semester', 'trimester'
            }
            $table->index(['academic_stream', 'calendar_type', 'is_current'], 'idx_session_stream_calendar_current');
        });

        // 2. Update programmes table
        Schema::table('programmes', function (Blueprint $table) {
            if (!Schema::hasColumn('programmes', 'academic_stream')) {
                $table->string('academic_stream')->default('UG')->after('type'); // 'UG', 'PG'
            }
            if (!Schema::hasColumn('programmes', 'calendar_type')) {
                $table->string('calendar_type')->default('semester')->after('academic_stream'); // 'semester', 'trimester'
            }
            if (!Schema::hasColumn('programmes', 'matric_format')) {
                $table->string('matric_format')->nullable()->after('calendar_type');
            }
            $table->index(['academic_stream', 'calendar_type'], 'idx_prog_stream_calendar');
        });

        // 3. Backfill existing PG/PHD programmes
        DB::table('programmes')
            ->whereIn('type', ['PG', 'PHD'])
            ->update([
                'academic_stream' => 'PG',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropIndex('idx_prog_stream_calendar');
            $table->dropColumn(['academic_stream', 'calendar_type', 'matric_format']);
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_session_stream_calendar_current');
            $table->dropColumn(['academic_stream', 'calendar_type']);
        });
    }
};
