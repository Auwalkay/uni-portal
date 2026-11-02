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
        Schema::table('applicant_qualifications', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_qualifications', 'scale_max')) {
                $table->decimal('scale_max', 3, 2)->default(5.00)->after('cgpa');
            }
            if (!Schema::hasColumn('applicant_qualifications', 'certificate_number')) {
                $table->string('certificate_number')->nullable()->after('graduation_year');
            }
            if (!Schema::hasColumn('applicant_qualifications', 'is_highest_qualification')) {
                $table->boolean('is_highest_qualification')->default(false)->after('certificate_path');
            }
            if (!Schema::hasColumn('applicant_qualifications', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(1)->after('is_highest_qualification');
            }
        });

        Schema::table('applicant_employments', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_employments', 'department_or_unit')) {
                $table->string('department_or_unit')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('applicant_employments', 'responsibilities')) {
                $table->text('responsibilities')->nullable()->after('is_current');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_qualifications', function (Blueprint $table) {
            $columns = ['scale_max', 'certificate_number', 'is_highest_qualification', 'sort_order'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('applicant_qualifications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('applicant_employments', function (Blueprint $table) {
            $columns = ['department_or_unit', 'responsibilities'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('applicant_employments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

