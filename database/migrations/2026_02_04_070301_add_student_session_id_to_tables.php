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
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->foreignUuid('student_session_id')->nullable()->after('student_id')->constrained('student_sessions')->cascadeOnDelete();
        });

        // Invoices table currently links to users, not students directly. 
        // We will add student_session_id as nullable for academic-related invoices.
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignUuid('student_session_id')->nullable()->after('user_id')->constrained('student_sessions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropForeign(['student_session_id']);
            $table->dropColumn('student_session_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['student_session_id']);
            $table->dropColumn('student_session_id');
        });
    }
};
