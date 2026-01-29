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
            $table->decimal('ca_score', 5, 2)->default(0)->after('semester_id');
            $table->decimal('exam_score', 5, 2)->default(0)->after('ca_score');
            $table->decimal('grade_point', 3, 2)->default(0)->after('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropColumn(['ca_score', 'exam_score', 'grade_point']);
        });
    }
};
