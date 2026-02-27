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
        Schema::create('applicants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('jamb_registration_number')->nullable()->unique();
            $table->string('application_mode')->default('UTME'); // UTME, DE, PG
            $table->string('status')->default('draft'); // draft, submitted, screening, admitted, rejected
            $table->string('program_choice_1')->nullable(); // Can be FK later
            $table->string('program_choice_2')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
