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
            $table->string('next_of_kin_name')->nullable()->after('research_interests');
            $table->string('next_of_kin_phone')->nullable()->after('next_of_kin_name');
            $table->text('next_of_kin_address')->nullable()->after('next_of_kin_phone');
            $table->string('next_of_kin_relationship')->nullable()->after('next_of_kin_address');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path', 2048)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_address', 'next_of_kin_relationship']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
        });
    }
};
