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
        Schema::table('hostels', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('description');
            $table->string('paystack_secret_key')->nullable()->after('squadco_public_key');
            $table->string('paystack_public_key')->nullable()->after('paystack_secret_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hostels', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'paystack_secret_key', 'paystack_public_key']);
        });
    }
};
