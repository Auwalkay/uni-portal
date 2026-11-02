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
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropUnique('academic_sessions_name_unique');
            $table->unique(['name', 'academic_stream', 'calendar_type'], 'sessions_name_stream_calendar_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropUnique('sessions_name_stream_calendar_unique');
            $table->unique('name', 'academic_sessions_name_unique');
        });
    }
};
