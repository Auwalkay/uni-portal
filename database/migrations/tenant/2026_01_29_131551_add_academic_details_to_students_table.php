<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('admitted_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->integer('program_duration')->default(4)->after('entry_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['admitted_session_id']);
            $table->dropColumn(['admitted_session_id', 'program_duration']);
        });
    }
};
