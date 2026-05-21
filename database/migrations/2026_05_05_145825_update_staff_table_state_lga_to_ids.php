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
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['state', 'lga']);
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null')->after('nationality');
            $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null')->after('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['staff_state_id_foreign']);
            $table->dropForeign(['staff_lga_id_foreign']);
            $table->dropColumn(['state_id', 'lga_id']);
            $table->string('state')->nullable()->after('nationality');
            $table->string('lga')->nullable()->after('state');
        });
    }
};
