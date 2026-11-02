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
        // 1. Digital Research Logbook (Candidate bi-weekly/monthly activities & supervisor review)
        if (!Schema::hasTable('postgraduate_research_logs')) {
            Schema::create('postgraduate_research_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('postgraduate_research_projects')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->date('entry_date')->index();
                $table->string('activity_summary', 255);
                $table->text('details_of_work_done');
                $table->text('challenges_and_next_steps')->nullable();
                $table->string('document_attachment_path')->nullable();

                // Supervisor Review
                $table->foreignUuid('reviewed_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->enum('supervisor_status', ['pending', 'reviewed', 'needs_revision'])->default('pending')->index();
                $table->text('supervisor_feedback')->nullable();
                $table->timestamps();

                // 5M-Scale Enterprise Compound Indexes
                $table->index(['project_id', 'entry_date']);
                $table->index(['student_id', 'supervisor_status']);
                $table->index(['reviewed_by_staff_id', 'supervisor_status']);
            });
        }

        // 2. Annual / Bi-annual Progress Reports (APR required for candidacy renewal)
        if (!Schema::hasTable('postgraduate_progress_reports')) {
            Schema::create('postgraduate_progress_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('postgraduate_research_projects')->cascadeOnDelete();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();
                $table->enum('reporting_period', ['first_semester', 'second_semester', 'annual'])->default('annual');
                $table->text('summary_of_progress');
                $table->text('chapters_completed');
                $table->text('fieldwork_or_lab_status');
                $table->text('publications_or_conferences')->nullable();
                $table->unsignedTinyInteger('completion_percentage')->default(0);

                // Supervisor Evaluation
                $table->enum('supervisor_rating', ['excellent', 'satisfactory', 'marginal', 'unsatisfactory'])->nullable();
                $table->text('supervisor_remarks')->nullable();
                $table->timestamp('supervisor_signed_at')->nullable();

                // DPGC / SPGS Sign-off
                $table->foreignUuid('dpgc_approved_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('dpgc_approved_at')->nullable();
                $table->enum('spgs_status', ['draft', 'submitted', 'supervisor_approved', 'spgs_certified'])->default('draft')->index();
                $table->timestamps();

                // 5M-Scale Enterprise Compound Indexes
                $table->index(['student_id', 'session_id']);
                $table->index(['project_id', 'reporting_period']);
                $table->index(['project_id', 'spgs_status']);
            });
        }

        // 3. Candidacy Petitions / Sundry Requests (Extension of Time, Deferment, Title Change)
        if (!Schema::hasTable('postgraduate_candidacy_requests')) {
            Schema::create('postgraduate_candidacy_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('project_id')->nullable()->constrained('postgraduate_research_projects')->nullOnDelete();
                $table->enum('request_type', [
                    'extension_of_time',
                    'deferment_of_candidacy',
                    'change_of_title',
                    'change_of_supervisor',
                    'upgrade_mphil_to_phd',
                ])->index();
                $table->string('proposed_title', 500)->nullable();
                $table->unsignedSmallInteger('extension_months')->nullable();
                $table->text('justification_and_timeline');
                $table->string('supporting_document_path')->nullable();
                $table->enum('status', ['pending', 'supervisor_recommended', 'dpgc_vetted', 'spgs_approved', 'rejected'])->default('pending')->index();
                $table->text('rejection_reason')->nullable();
                $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                // 5M-Scale Enterprise Compound Indexes
                $table->index(['student_id', 'status']);
                $table->index(['request_type', 'status']);
            });
        }

        // 4. Research Publications Tracker (Journal paper mandate for Ph.D / Master's)
        if (!Schema::hasTable('postgraduate_publications')) {
            Schema::create('postgraduate_publications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignUuid('project_id')->constrained('postgraduate_research_projects')->cascadeOnDelete();
                $table->string('article_title', 500);
                $table->string('journal_name', 255);
                $table->string('issn_isbn', 50)->nullable();
                $table->string('doi_or_url', 255)->nullable();
                $table->string('indexing_agency', 100)->nullable(); // Scopus, Web of Science, Google Scholar, NUC-Recognized
                $table->enum('publication_status', ['submitted', 'accepted_in_press', 'published'])->default('published');
                $table->string('evidence_document_path')->nullable();
                $table->boolean('is_spgs_verified')->default(false)->index();
                $table->timestamps();

                // 5M-Scale Enterprise Compound Indexes
                $table->index(['student_id', 'is_spgs_verified']);
                $table->index(['project_id', 'publication_status']);
            });
        }

        // 5. External & Internal Examiners (Dispatch tracking, pre-viva reports, honoraria)
        if (!Schema::hasTable('postgraduate_examiners')) {
            Schema::create('postgraduate_examiners', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('postgraduate_research_projects')->cascadeOnDelete();
                $table->enum('examiner_type', ['internal_examiner', 'external_examiner', 'spgs_representative'])->index();
                $table->string('full_name', 255);
                $table->string('title', 50)->default('Prof.');
                $table->string('institution_or_department', 255);
                $table->string('email', 255)->index();
                $table->string('phone', 50)->nullable();
                $table->string('specialization', 255)->nullable();
                $table->date('draft_dispatched_at')->nullable();
                $table->string('dispatch_tracking_ref')->nullable();
                $table->date('report_received_at')->nullable();
                $table->enum('pre_viva_recommendation', ['proceed_to_viva', 'minor_revisions_first', 'unacceptable'])->nullable();
                $table->text('examiner_comments')->nullable();
                $table->string('report_file_path')->nullable();
                $table->boolean('honorarium_paid')->default(false)->index();
                $table->timestamps();

                // 5M-Scale Enterprise Compound Indexes
                $table->index(['project_id', 'examiner_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postgraduate_examiners');
        Schema::dropIfExists('postgraduate_publications');
        Schema::dropIfExists('postgraduate_candidacy_requests');
        Schema::dropIfExists('postgraduate_progress_reports');
        Schema::dropIfExists('postgraduate_research_logs');
    }
};
