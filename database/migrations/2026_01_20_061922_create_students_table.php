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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('matriculation_number')->unique()->nullable(); // Null until generated
            $table->string('program')->nullable(); // FK later
            $table->string('department')->nullable(); // FK later
            $table->string('faculty')->nullable(); // FK later
            $table->integer('current_level')->default(100);
            $table->string('entry_mode')->default('UTME');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
