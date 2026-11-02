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
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'title')) {
                $table->string('title', 30)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('applicants', 'marital_status')) {
                $table->string('marital_status', 30)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('applicants', 'nationality')) {
                $table->string('nationality', 100)->default('Nigerian')->after('marital_status');
            }
            if (!Schema::hasColumn('applicants', 'has_name_change')) {
                $table->boolean('has_name_change')->default(false)->after('nationality');
            }
            if (!Schema::hasColumn('applicants', 'previous_full_name')) {
                $table->string('previous_full_name')->nullable()->after('has_name_change');
            }
            if (!Schema::hasColumn('applicants', 'study_mode')) {
                $table->string('study_mode', 40)->default('Full-Time')->after('application_mode');
            }
            if (!Schema::hasColumn('applicants', 'area_of_specialization')) {
                $table->string('area_of_specialization')->nullable()->after('program_choice_2');
            }
            if (!Schema::hasColumn('applicants', 'sponsorship_type')) {
                $table->string('sponsorship_type', 50)->default('Self')->after('area_of_specialization');
            }
            if (!Schema::hasColumn('applicants', 'sponsor_name')) {
                $table->string('sponsor_name')->nullable()->after('sponsorship_type');
            }
            if (!Schema::hasColumn('applicants', 'nysc_certificate_number')) {
                $table->string('nysc_certificate_number', 100)->nullable()->after('nysc_status');
            }
            if (!Schema::hasColumn('applicants', 'official_transcript_status')) {
                $table->string('official_transcript_status', 50)->default('pending_dispatch')->after('research_proposal_title');
            }
        });

        if (!Schema::hasTable('applicant_qualifications')) {
            Schema::create('applicant_qualifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('applicant_id')->constrained('applicants')->onDelete('cascade');
                $table->string('qualification_type', 50); // HND, B.Sc, PGD, M.Sc, MBA, M.Phil
                $table->string('institution_name');
                $table->string('course_of_study')->nullable();
                $table->string('matric_number', 100)->nullable();
                $table->string('class_of_degree', 100);
                $table->decimal('cgpa', 3, 2)->nullable();
                $table->decimal('cgpa_scale', 3, 2)->default(5.00);
                $table->decimal('scale_max', 3, 2)->default(5.00);
                $table->unsignedSmallInteger('graduation_year')->nullable();
                $table->string('certificate_number')->nullable();
                $table->string('certificate_path')->nullable();
                $table->boolean('is_highest_qualification')->default(false);
                $table->unsignedInteger('sort_order')->default(1);
                $table->timestamps();

                $table->index(['applicant_id', 'qualification_type']);
            });
        }

        if (!Schema::hasTable('applicant_employments')) {
            Schema::create('applicant_employments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('applicant_id')->constrained('applicants')->onDelete('cascade');
                $table->string('employer_name');
                $table->string('job_title');
                $table->string('department_or_unit')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('responsibilities')->nullable();
                $table->timestamps();

                $table->index('applicant_id');
            });
        }

        if (!Schema::hasTable('postgraduate_student_profiles')) {
            Schema::create('postgraduate_student_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->unique()->constrained('students')->onDelete('cascade');
                $table->foreignUuid('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
                $table->string('spgs_file_number')->unique();
                $table->string('study_mode', 40)->default('Full-Time'); // Full-Time, Part-Time, Executive
                $table->string('area_of_specialization')->nullable();
                $table->string('sponsorship_type', 50)->default('Self');
                $table->string('sponsor_name')->nullable();
                $table->string('marital_status', 30)->nullable();
                $table->string('nationality', 100)->default('Nigerian');
                $table->string('nysc_status', 50)->nullable();
                $table->string('nysc_certificate_number', 100)->nullable();
                $table->string('highest_entry_qualification', 50)->nullable();
                $table->string('entry_institution')->nullable();
                $table->string('entry_course_of_study')->nullable();
                $table->string('entry_class_of_degree', 100)->nullable();
                $table->decimal('entry_cgpa', 3, 2)->nullable();
                $table->unsignedSmallInteger('entry_graduation_year')->nullable();
                $table->json('prior_qualifications_snapshot')->nullable();
                $table->json('employment_history_snapshot')->nullable();
                $table->unsignedTinyInteger('work_experience_years')->default(0);
                $table->string('official_transcript_status', 50)->default('pending_dispatch');
                $table->timestamp('transcript_received_at')->nullable();
                $table->foreignUuid('transcript_verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('sessions_enrolled_count')->default(1);
                $table->unsignedTinyInteger('min_duration_semesters')->default(3);
                $table->unsignedTinyInteger('max_duration_semesters')->default(6);
                $table->string('candidacy_status', 40)->default('regular'); // regular, extension, completed, withdrawn
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('postgraduate_student_profiles');
        Schema::dropIfExists('applicant_employments');
        Schema::dropIfExists('applicant_qualifications');

        Schema::table('applicants', function (Blueprint $table) {
            $columns = [
                'title',
                'marital_status',
                'nationality',
                'has_name_change',
                'previous_full_name',
                'study_mode',
                'area_of_specialization',
                'sponsorship_type',
                'sponsor_name',
                'nysc_certificate_number',
                'official_transcript_status',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

