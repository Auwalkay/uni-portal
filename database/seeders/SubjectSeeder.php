<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'English Language',
            'Mathematics',
            'Biology',
            'Physics',
            'Chemistry',
            'Agricultural Science',
            'Government',
            'Economics',
            'Geography',
            'Literature in English',
            'History',
            'Christian Religious Studies',
            'Islamic Religious Studies',
            'French',
            'Civic Education',
            'Computer Studies',
            'Data Processing',
            'Commerce',
            'Financial Accounting',
            'Further Mathematics',
            'Technical Drawing',
            'Foods and Nutrition',
            'Home Management',
            'Visual Arts',
            'Music',
            'Igbo',
            'Hausa',
            'Yoruba',
            'Marketing',
            'Animal Husbandry',
            'Fisheries',
            'Dyeing and Bleaching',
            'Painting and Decorating',
            'Photography',
            'Tourism',
            'Mining',
            'Book Keeping',
            'Store Keeping',
            'Office Practice',
            'Insurance',
            'Salesmanship',
        ];

        foreach ($subjects as $subject) {
            \App\Models\Subject::firstOrCreate(['name' => $subject]);
        }
    }
}
