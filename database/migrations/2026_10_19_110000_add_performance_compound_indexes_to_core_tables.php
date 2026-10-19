<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'status'], 'idx_invoices_user_type_status');
            $table->index(['session_id', 'type', 'status'], 'idx_invoices_session_type_status');
            $table->index('due_date', 'idx_invoices_due_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['invoice_id', 'status'], 'idx_payments_invoice_status');
            $table->index(['user_id', 'status'], 'idx_payments_user_status');
            $table->index(['status', 'paid_at'], 'idx_payments_status_paid_at');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index(['department_id', 'current_level'], 'idx_students_dept_level');
            $table->index(['faculty_id', 'department_id'], 'idx_students_faculty_dept');
            $table->index(['admitted_session_id', 'current_level'], 'idx_students_session_level');
            $table->index('status', 'idx_students_status');
        });

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->index(['student_id', 'session_id', 'semester_id', 'is_published'], 'idx_cr_student_session_sem');
            $table->index(['course_id', 'session_id', 'semester_id'], 'idx_cr_course_session_sem');
        });

        Schema::table('fee_configurations', function (Blueprint $table) {
            $table->index(['session_id', 'department_id', 'level'], 'idx_fc_session_dept_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ensure standalone foreign key indexes exist before dropping compound indexes
        // so MySQL does not raise error 1553: Needed in a foreign key constraint.
        $this->ensureIndex('invoices', 'user_id', 'invoices_user_id_foreign');
        $this->ensureIndex('invoices', 'session_id', 'invoices_session_id_foreign');
        $this->ensureIndex('payments', 'invoice_id', 'payments_invoice_id_foreign');
        $this->ensureIndex('payments', 'user_id', 'payments_user_id_foreign');
        $this->ensureIndex('students', 'department_id', 'students_department_id_foreign');
        $this->ensureIndex('students', 'faculty_id', 'students_faculty_id_foreign');
        $this->ensureIndex('students', 'admitted_session_id', 'students_admitted_session_id_foreign');
        $this->ensureIndex('course_registrations', 'student_id', 'course_registrations_student_id_foreign');
        $this->ensureIndex('course_registrations', 'course_id', 'course_registrations_course_id_foreign');
        $this->ensureIndex('fee_configurations', 'session_id', 'fee_configurations_session_id_foreign');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_user_type_status');
            $table->dropIndex('idx_invoices_session_type_status');
            $table->dropIndex('idx_invoices_due_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_invoice_status');
            $table->dropIndex('idx_payments_user_status');
            $table->dropIndex('idx_payments_status_paid_at');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_dept_level');
            $table->dropIndex('idx_students_faculty_dept');
            $table->dropIndex('idx_students_session_level');
            $table->dropIndex('idx_students_status');
        });

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropIndex('idx_cr_student_session_sem');
            $table->dropIndex('idx_cr_course_session_sem');
        });

        Schema::table('fee_configurations', function (Blueprint $table) {
            $table->dropIndex('idx_fc_session_dept_level');
        });
    }

    private function ensureIndex(string $table, string $column, string $indexName): void
    {
        try {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"))->pluck('Key_name')->all();
            if (!in_array($indexName, $indexes)) {
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)");
            }
        } catch (\Throwable $e) {
            // Index already exists or table not available
        }
    }
};
