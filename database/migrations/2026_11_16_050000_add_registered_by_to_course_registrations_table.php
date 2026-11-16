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
            if (!Schema::hasColumn('course_registrations', 'registered_by')) {
                $table->foreignUuid('registered_by')->nullable()->after('student_session_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('course_registrations', 'registration_source')) {
                $table->string('registration_source', 20)->default('student')->after('registered_by');
            }
            $table->index(['session_id', 'registration_source'], 'idx_cr_session_reg_source');
            $table->index(['registered_by'], 'idx_cr_registered_by');
        });

        // Safely backfill historical records
        try {
            DB::table('course_registrations')
                ->whereNull('registered_by')
                ->update(['registration_source' => 'student']);
        } catch (\Throwable $e) {
            // Ignore if in test environment with empty table
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropIndex('idx_cr_session_reg_source');
            $table->dropIndex('idx_cr_registered_by');
            $table->dropForeign(['registered_by']);
            $table->dropColumn(['registered_by', 'registration_source']);
        });
    }
};
