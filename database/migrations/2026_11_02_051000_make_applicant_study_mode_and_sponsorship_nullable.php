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
            $table->string('study_mode', 40)->nullable()->default('Full-Time')->change();
            $table->string('sponsorship_type', 50)->nullable()->default('Self')->change();
            $table->string('official_transcript_status', 50)->nullable()->default('not_requested')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('study_mode', 40)->default('Full-Time')->change();
            $table->string('sponsorship_type', 50)->default('Self')->change();
            $table->string('official_transcript_status', 50)->default('pending_dispatch')->change();
        });
    }
};

