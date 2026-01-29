<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Session;
use Illuminate\Database\Seeder;

class AcademicRecordsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Current Session
        $session = Session::firstOrCreate(
            ['name' => '2025/2026'],
            [
                'start_date' => '2025-09-01',
                'end_date' => '2026-07-31',
                'is_current' => true,
            ]
        );

        // 2. Create Semesters
        $firstSemester = Semester::firstOrCreate(
            ['name' => 'First Semester', 'session_id' => $session->id],
            ['is_current' => true]
        );

        Semester::firstOrCreate(
            ['name' => 'Second Semester', 'session_id' => $session->id],
            ['is_current' => false]
        );

        // 3. Create Courses for CSC Dept (assuming it exists, or fetch any)
        $cscDept = Department::where('code', 'CSC')->first();
        if ($cscDept) {
            $courses = [
                ['code' => 'CSC 101', 'title' => 'Introduction to Computer Science', 'units' => 3, 'level' => 100, 'semester' => '1'],
                ['code' => 'CSC 102', 'title' => 'Introduction to Programming', 'units' => 3, 'level' => 100, 'semester' => '2'],
                ['code' => 'MTH 101', 'title' => 'General Mathematics I', 'units' => 3, 'level' => 100, 'semester' => '1'],
                ['code' => 'MTH 102', 'title' => 'General Mathematics II', 'units' => 3, 'level' => 100, 'semester' => '2'],
                ['code' => 'PHY 101', 'title' => 'General Physics I', 'units' => 3, 'level' => 100, 'semester' => '1'],
                ['code' => 'GNS 101', 'title' => 'Use of English I', 'units' => 2, 'level' => 100, 'semester' => '1'],
            ];

            foreach ($courses as $course) {
                Course::firstOrCreate(
                    ['code' => $course['code'], 'department_id' => $cscDept->id],
                    $course
                );
            }
        }
    }
}
