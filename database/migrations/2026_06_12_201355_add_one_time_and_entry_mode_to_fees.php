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
        Schema::table('fee_types', function (Blueprint $table) {
            $table->boolean('is_one_time')->default(false)->after('description');
        });

        Schema::table('fee_configurations', function (Blueprint $table) {
            $table->string('entry_mode')->nullable()->after('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn('is_one_time');
        });

        Schema::table('fee_configurations', function (Blueprint $table) {
            $table->dropColumn('entry_mode');
        });
    }
};

