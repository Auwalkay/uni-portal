<?php

// Skip AcademicSeeder as it seems data exists and it doesn't use firstOrCreate everywhere
// $seeder = new \Database\Seeders\AcademicSeeder();
// $seeder->run();

// Seed Records (Courses, Sessions) - safe to run
$seeder2 = new \Database\Seeders\AcademicRecordsSeeder();
$seeder2->run();

// Create Student
$user = \App\Models\User::firstOrCreate(['email' => 'student@portal.com'], [
    'name' => 'John',
    'last_name' => 'Doe',
    'password' => bcrypt('password')
]);

// Assign Role
try {
    $user->assignRole('student');
} catch (\Exception $e) {
    echo "Role assignment failed: " . $e->getMessage() . "\n";
}

$dept = \App\Models\Department::where('code', 'CSC')->first();
if (!$dept)
    echo "Warning: CSC Department not found.\n";

\App\Models\Student::updateOrCreate(['user_id' => $user->id], [
    'matriculation_number' => 'MAT123456',
    'department' => $dept ? $dept->name : 'Computer Science',
    'current_level' => 100,
    'program' => 'B.Sc Computer Science',
    'faculty' => 'Faculty of Sciences',
    'entry_mode' => 'UTME'
]);

echo "Setup Complete\n";
