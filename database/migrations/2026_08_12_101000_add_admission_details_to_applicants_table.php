<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('admitted_level')->nullable()->after('scholarship_id');
            $table->string('admitted_programme_id')->nullable()->after('admitted_level');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['admitted_level', 'admitted_programme_id']);
        });
    }
};
