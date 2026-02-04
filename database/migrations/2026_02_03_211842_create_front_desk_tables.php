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
        Schema::create('front_desk_visitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('visitor_name');
            $table->string('phone');
            $table->string('purpose');
            $table->string('whom_to_see')->nullable();
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->foreignUuid('receptionist_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('front_desk_complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('complainant_name');
            $table->string('phone');
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->text('resolution_notes')->nullable();
            $table->foreignUuid('receptionist_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('front_desk_enquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('inquirer_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('inquiry');
            $table->text('response')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignUuid('receptionist_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('front_desk_enquiries');
        Schema::dropIfExists('front_desk_complaints');
        Schema::dropIfExists('front_desk_visitors');
    }
};
