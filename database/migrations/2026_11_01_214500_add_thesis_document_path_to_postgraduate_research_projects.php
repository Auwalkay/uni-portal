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
        Schema::table('postgraduate_research_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('postgraduate_research_projects', 'thesis_document_path')) {
                $table->string('thesis_document_path')->nullable()->after('research_area');
                $table->timestamp('thesis_submitted_at')->nullable()->after('thesis_document_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('postgraduate_research_projects', function (Blueprint $table) {
            if (Schema::hasColumn('postgraduate_research_projects', 'thesis_document_path')) {
                $table->dropColumn(['thesis_document_path', 'thesis_submitted_at']);
            }
        });
    }
};

