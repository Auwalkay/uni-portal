<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sickbay_medical_logs', function (Blueprint $table) {
            $table->json('recommended_tests')->nullable();
            $table->json('external_prescriptions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sickbay_medical_logs', function (Blueprint $table) {
            $table->dropColumn(['recommended_tests', 'external_prescriptions']);
        });
    }
};
