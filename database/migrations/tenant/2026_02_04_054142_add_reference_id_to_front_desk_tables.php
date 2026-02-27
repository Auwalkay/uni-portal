<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('front_desk_visitors', function (Blueprint $table) {
            $table->string('reference_id')->unique()->after('id');
        });

        Schema::table('front_desk_complaints', function (Blueprint $table) {
            $table->string('reference_id')->unique()->after('id');
        });

        Schema::table('front_desk_enquiries', function (Blueprint $table) {
            $table->string('reference_id')->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('front_desk_visitors', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });

        Schema::table('front_desk_complaints', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });

        Schema::table('front_desk_enquiries', function (Blueprint $table) {
            $table->dropColumn('reference_id');
        });
    }
};
