<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\LibraryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Computer Science', 'description' => 'Programming, Algorithms, Data Science and Computing.'],
            ['name' => 'Mathematics', 'description' => 'Calculus, Algebra, Statistics and Quantitative Methods.'],
            ['name' => 'Engineering', 'description' => 'Electrical, Mechanical, and Civil Engineering core books.'],
            ['name' => 'Business & Economics', 'description' => 'Finance, Marketing, Management, and Economic theories.'],
        ];

        foreach ($categories as $cat) {
            $category = LibraryCategory::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'description' => $cat['description'],
            ]);

            // Add dummy physical book
            Book::create([
                'library_category_id' => $category->id,
                'title' => "Introduction to Algorithms in {$cat['name']}",
                'author' => 'Dr. Jane Smith',
                'isbn' => '978-' . rand(1000000000, 9999999999),
                'publisher' => 'Academic Press',
                'publish_year' => 2022,
                'is_ebook' => false,
                'total_copies' => 5,
                'available_copies' => 5,
                'shelf_location' => 'Shelf A-' . rand(1, 10),
                'description' => 'A foundational physical textbook detailing major concepts in ' . $cat['name'],
            ]);

            // Add dummy e-book
            Book::create([
                'library_category_id' => $category->id,
                'title' => "Advanced Digital Guide: {$cat['name']}",
                'author' => 'Prof. Alan Turing',
                'isbn' => '978-' . rand(1000000000, 9999999999),
                'publisher' => 'E-University Press',
                'publish_year' => 2024,
                'is_ebook' => true,
                'ebook_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf', // Placeholder PDF link
                'description' => 'An instantly accessible digital handbook for ' . $cat['name'],
            ]);
        }
    }
}
