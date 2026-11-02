<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Staff Postgraduate Supervisory Accreditation & Capacity Quotas
        Schema::create('postgraduate_staff_accreditations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('staff', 'id', 'fk_pg_accred_staff')->onDelete('cascade');
            $table->boolean('is_phd_accredited')->default(false);
            $table->boolean('is_masters_accredited')->default(true);
            $table->boolean('is_pgd_accredited')->default(true);
            $table->unsignedInteger('max_phd_quota')->default(5);
            $table->unsignedInteger('max_masters_quota')->default(8);
            $table->unsignedInteger('active_phd_count')->default(0);
            $table->unsignedInteger('active_masters_count')->default(0);
            $table->json('specialization_areas')->nullable();
            $table->string('scopus_id', 100)->nullable();
            $table->string('orcid_id', 100)->nullable();
            $table->string('google_scholar_url', 255)->nullable();
            $table->foreignUuid('accredited_by_user_id')->nullable()->constrained('users', 'id', 'fk_pg_accred_user')->nullOnDelete();
            $table->timestamp('accredited_at')->nullable();
            $table->timestamps();

            $table->index(['is_phd_accredited', 'is_masters_accredited'], 'idx_pg_accred_ranks');
            $table->index(['active_phd_count', 'active_masters_count'], 'idx_pg_accred_load');
        });

        // 2. Master External Examiners Registry (Institutional Reuse & Tenure Tracking)
        Schema::create('postgraduate_external_examiners_registry', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 50)->default('Prof.');
            $table->string('full_name', 255);
            $table->string('institution', 255);
            $table->string('department', 255);
            $table->string('email', 255)->unique();
            $table->string('phone', 50)->nullable();
            $table->string('specialization', 255);
            $table->foreignUuid('first_appointed_session_id')->nullable()->constrained('academic_sessions', 'id', 'fk_ext_exam_sess_first')->nullOnDelete();
            $table->foreignUuid('last_appointed_session_id')->nullable()->constrained('academic_sessions', 'id', 'fk_ext_exam_sess_last')->nullOnDelete();
            $table->unsignedInteger('consecutive_sessions_count')->default(1);
            $table->unsignedInteger('total_theses_examined')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['institution'], 'idx_ext_exam_inst');
            $table->index(['specialization'], 'idx_ext_exam_spec');
            $table->index(['is_active'], 'idx_ext_exam_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postgraduate_external_examiners_registry');
        Schema::dropIfExists('postgraduate_staff_accreditations');
    }
};
