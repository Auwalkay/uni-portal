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
        Schema::table('course_allocations', function (Blueprint $table) {
            $table->index(['session_id', 'course_id'], 'idx_alloc_session_course');
            $table->index(['staff_id', 'session_id'], 'idx_alloc_staff_session');
            $table->index(['department_id', 'session_id'], 'idx_alloc_dept_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_allocations', function (Blueprint $table) {
            $table->dropIndex('idx_alloc_session_course');
            $table->dropIndex('idx_alloc_staff_session');
            $table->dropIndex('idx_alloc_dept_session');
        });
    }
};
