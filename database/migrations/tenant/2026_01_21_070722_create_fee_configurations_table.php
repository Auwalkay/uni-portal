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
        Schema::create('fee_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();

            // Session uses UUID and table 'academic_sessions'
            $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();

            // Filters use UUIDs
            $table->foreignUuid('faculty_id')->nullable()->constrained('faculties')->onDelete('cascade');
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignUuid('program_id')->nullable()->constrained('programmes')->onDelete('cascade');

            $table->string('level')->nullable(); // 100, 200, etc.

            $table->decimal('amount', 12, 2);
            $table->boolean('is_compulsory')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_configurations');
    }
};
