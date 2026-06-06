<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('library_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_category_id')->constrained('library_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->nullable()->unique();
            $table->string('publisher')->nullable();
            $table->integer('publish_year')->nullable();
            $table->boolean('is_ebook')->default(false); // E-Book Flag
            $table->string('ebook_file_path')->nullable(); // Local PDF/EPUB storage path
            $table->string('ebook_url')->nullable(); // External link
            $table->integer('total_copies')->default(1); // Ignored if is_ebook is true
            $table->integer('available_copies')->default(1); // Ignored if is_ebook is true
            $table->string('shelf_location')->nullable(); // Ignored if is_ebook is true
            $table->text('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('book_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('borrowed_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'returned', 'overdue'])->default('pending');
            $table->text('user_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_loans');
        Schema::dropIfExists('books');
        Schema::dropIfExists('library_categories');
    }
};
