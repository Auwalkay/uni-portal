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
        Schema::create('inventory_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('assignable_type');
            $table->uuid('assignable_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expected_return_date')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->enum('status', ['assigned', 'returned', 'lost', 'damaged'])->default('assigned');
            $table->string('condition_on_assignment')->nullable();
            $table->string('condition_on_return')->nullable();
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_assignments');
    }
};
