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
        Schema::table('applicants', function (Blueprint $table) {
            $table->foreignUuid('scholarship_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('scholarship_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropForeign(['scholarship_id']);
            $table->dropColumn('scholarship_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['scholarship_id']);
            $table->dropColumn('scholarship_id');
        });
    }
};
