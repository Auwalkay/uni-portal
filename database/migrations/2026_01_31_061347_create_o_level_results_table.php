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
        Schema::create('o_level_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->string('exam_type'); // WAEC, NECO, etc.
            $table->string('exam_year');
            $table->string('exam_number');
            $table->string('center_number')->nullable();
            $table->json('subjects'); // Stores array of {subject: "Maths", grade: "A1"}
            $table->string('scanned_copy_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('o_level_results');
    }
};
