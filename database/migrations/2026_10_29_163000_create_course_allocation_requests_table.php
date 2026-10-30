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
        Schema::create('course_allocation_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignUuid('department_id')->constrained('departments')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            // High-performance composite indexes for 1M+ scale
            $table->index(['session_id', 'course_id', 'status'], 'idx_req_session_course_status');
            $table->index(['staff_id', 'session_id', 'status'], 'idx_req_staff_session_status');
            $table->index(['department_id', 'status', 'created_at'], 'idx_req_dept_status_created');
            $table->index(['status', 'created_at'], 'idx_req_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_allocation_requests');
    }
};
