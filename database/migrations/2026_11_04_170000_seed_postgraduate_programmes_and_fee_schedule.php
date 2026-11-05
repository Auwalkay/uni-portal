<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Set PG application / enrolment fee in system settings to ₦50,000
        SystemSetting::updateOrCreate(
            ['key' => 'pg_application_fee'],
            ['value' => 50000]
        );

        if (app()->runningUnitTests()) {
            return;
        }

        // 2. Provision the 30 Postgraduate programmes and attach their fee rules
        Artisan::call('fees:seed-pg-schedule');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'pg_application_fee')->delete();
    }
};
