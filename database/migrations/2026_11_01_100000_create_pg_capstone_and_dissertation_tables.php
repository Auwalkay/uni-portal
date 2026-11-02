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
        if (!Schema::hasTable('postgraduate_research_projects')) {
            Schema::create('postgraduate_research_projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignUuid('session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
                $table->foreignUuid('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->foreignUuid('course_registration_id')->nullable()->constrained('course_registrations')->nullOnDelete();
                $table->string('pathway_type')->default('academic_dissertation')->index(); // pgd_project, capstone_consulting, academic_dissertation
                $table->string('title');
                $table->text('abstract')->nullable();
                $table->string('research_area')->nullable();

                // Supervisors & External Examiner
                $table->foreignUuid('major_supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->foreignUuid('co_supervisor_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->foreignUuid('co_supervisor_2_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->string('external_examiner_name')->nullable();
                $table->string('external_examiner_institution')->nullable();
                $table->string('external_examiner_email')->nullable();

                // Lifecycle & Viva Voce Defense
                $table->string('status')->default('topic_proposed')->index();
                $table->string('defense_status')->default('unscheduled')->index();
                $table->timestamp('defense_date')->nullable();
                $table->string('defense_venue')->nullable();
                $table->text('defense_panel_notes')->nullable();
                $table->decimal('final_score', 5, 2)->nullable();
                $table->string('final_grade', 5)->nullable();
                $table->decimal('final_grade_point', 3, 2)->nullable();
                $table->string('repository_url')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'status']);
                $table->index(['major_supervisor_id', 'status']);
            });
        }

        if (!Schema::hasTable('postgraduate_research_milestones')) {
            Schema::create('postgraduate_research_milestones', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('project_id')->constrained('postgraduate_research_projects')->onDelete('cascade');
                $table->string('stage')->index();
                $table->string('title');
                $table->unsignedTinyInteger('sequence_order')->default(1);
                $table->text('submission_notes')->nullable();
                $table->string('document_path')->nullable();
                $table->decimal('plagiarism_similarity_index', 5, 2)->nullable();
                $table->string('status')->default('pending')->index(); // pending, submitted, revision_requested, supervisor_approved, spgs_cleared
                $table->text('supervisor_remarks')->nullable();
                $table->text('spgs_remarks')->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['project_id', 'stage']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postgraduate_research_milestones');
        Schema::dropIfExists('postgraduate_research_projects');
    }
};

