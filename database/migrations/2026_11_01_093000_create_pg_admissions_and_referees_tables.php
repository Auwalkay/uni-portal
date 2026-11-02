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
            if (!Schema::hasColumn('applicants', 'previous_institution')) {
                $table->string('previous_institution')->nullable()->after('jamb_score');
            }
            if (!Schema::hasColumn('applicants', 'previous_qualification')) {
                $table->string('previous_qualification')->nullable()->after('previous_institution');
            }
            if (!Schema::hasColumn('applicants', 'previous_course_of_study')) {
                $table->string('previous_course_of_study')->nullable()->after('previous_qualification');
            }
            if (!Schema::hasColumn('applicants', 'previous_class_of_degree')) {
                $table->string('previous_class_of_degree')->nullable()->after('previous_course_of_study');
            }
            if (!Schema::hasColumn('applicants', 'previous_cgpa')) {
                $table->decimal('previous_cgpa', 3, 2)->nullable()->after('previous_class_of_degree');
            }
            if (!Schema::hasColumn('applicants', 'graduation_year')) {
                $table->unsignedSmallInteger('graduation_year')->nullable()->after('previous_cgpa');
            }
            if (!Schema::hasColumn('applicants', 'nysc_status')) {
                $table->string('nysc_status')->nullable()->after('graduation_year');
            }
            if (!Schema::hasColumn('applicants', 'work_experience_years')) {
                $table->unsignedTinyInteger('work_experience_years')->default(0)->after('nysc_status');
            }
            if (!Schema::hasColumn('applicants', 'statement_of_purpose')) {
                $table->text('statement_of_purpose')->nullable()->after('work_experience_years');
            }
            if (!Schema::hasColumn('applicants', 'research_proposal_title')) {
                $table->string('research_proposal_title')->nullable()->after('statement_of_purpose');
            }
        });

        if (!Schema::hasTable('applicant_referees')) {
            Schema::create('applicant_referees', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('applicant_id')->constrained('applicants')->onDelete('cascade');
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('institution_or_organization');
                $table->string('position_or_rank')->nullable();
                $table->string('relationship')->nullable();
                $table->string('token', 64)->unique();
                $table->string('status')->default('pending')->index(); // pending, submitted, declined
                $table->unsignedTinyInteger('academic_capacity_rating')->nullable();
                $table->unsignedTinyInteger('research_potential_rating')->nullable();
                $table->unsignedTinyInteger('character_integrity_rating')->nullable();
                $table->unsignedTinyInteger('communication_rating')->nullable();
                $table->string('recommendation_strength')->nullable();
                $table->text('comments')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['applicant_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicant_referees');

        Schema::table('applicants', function (Blueprint $table) {
            $columns = [
                'previous_institution',
                'previous_qualification',
                'previous_course_of_study',
                'previous_class_of_degree',
                'previous_cgpa',
                'graduation_year',
                'nysc_status',
                'work_experience_years',
                'statement_of_purpose',
                'research_proposal_title',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

