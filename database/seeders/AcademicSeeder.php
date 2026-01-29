<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AcademicSeeder extends Seeder
{
    public function run()
    {
        $faculties = [
            'Faculty of Sciences' => [
                'code' => 'SCI',
                'departments' => [
                    'Computer Science' => ['code' => 'CSC', 'programmes' => ['B.Sc Computer Science', 'B.Sc Software Engineering', 'B.Sc Cyber Security']],
                    'Mathematics' => ['code' => 'MTH', 'programmes' => ['B.Sc Mathematics', 'B.Sc Statistics']],
                    'Physics' => ['code' => 'PHY', 'programmes' => ['B.Sc Physics', 'B.Sc Geophysics']],
                ]
            ],
            'Faculty of Arts' => [
                'code' => 'ART',
                'departments' => [
                    'English' => ['code' => 'ENG', 'programmes' => ['B.A English Language', 'B.A Literature']],
                    'History' => ['code' => 'HIS', 'programmes' => ['B.A History & International Studies']],
                ]
            ],
            'Faculty of Engineering' => [
                'code' => 'ENG',
                'departments' => [
                    'Electrical Engineering' => ['code' => 'EEE', 'programmes' => ['B.Eng Electrical & Electronics Engineering']],
                    'Mechanical Engineering' => ['code' => 'MEE', 'programmes' => ['B.Eng Mechanical Engineering']],
                ]
            ],
            'Faculty of Management Sciences' => [
                'code' => 'MGT',
                'departments' => [
                    'Accounting' => ['code' => 'ACC', 'programmes' => ['B.Sc Accounting']],
                    'Business Administration' => ['code' => 'BUS', 'programmes' => ['B.Sc Business Administration']],
                ]
            ],
        ];

        foreach ($faculties as $facultyName => $data) {
            $faculty = Faculty::create([
                'name' => $facultyName,
                'code' => $data['code'],
            ]);

            foreach ($data['departments'] as $deptName => $deptData) {
                $department = $faculty->departments()->create([
                    'name' => $deptName,
                    'code' => $deptData['code'],
                ]);

                foreach ($deptData['programmes'] as $progName) {
                    $department->programmes()->create([
                        'name' => $progName,
                        'type' => 'UG',
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
