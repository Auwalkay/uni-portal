<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $designations = [
            'Professor',
            'Associate Professor',
            'Senior Lecturer',
            'Lecturer I',
            'Lecturer II',
            'Assistant Lecturer',
            'Graduate Assistant',
            'Technologist',
            'Senior Technologist',
            'Administrative Officer',
            'Assistant Registrar',
            'Senior Assistant Registrar',
            'Principal Assistant Registrar',
            'Deputy Registrar',
            'Registrar',
            'Bursar',
            'Librarian',
            'Medical Officer',
            'Coach',
            'Porter',
            'Driver',
            'Security Officer',
        ];

        foreach ($designations as $name) {
            \App\Models\Designation::updateOrCreate(['name' => $name]);
        }
    }
}
