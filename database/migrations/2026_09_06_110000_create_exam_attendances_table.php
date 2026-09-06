<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_schedule_id')->constrained('exam_schedules')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_token')->nullable();
            $table->timestamp('verified_at')->useCurrent();
            $table->string('status')->default('present'); // present, late, flagged
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['exam_schedule_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attendances');
    }
};
