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
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dateTime('late_payment_deadline')->nullable()->after('end_date');
            $table->boolean('school_fee_payment_enabled')->default(true)->after('late_payment_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn(['late_payment_deadline', 'school_fee_payment_enabled']);
        });
    }
};
