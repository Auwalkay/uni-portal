<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NigeriaAcademicSeeder extends Seeder
{
    public function run()
    {
        $faculties = [
            'Faculty of Sciences' => [
                'code' => 'SCI',
                'departments' => [
                    'Computer Science' => [
                        'code' => 'CSC',
                        'programmes' => ['B.Sc Computer Science', 'B.Sc Software Engineering', 'B.Sc Cyber Security']
                    ],
                    'Mathematics' => [
                        'code' => 'MTH',
                        'programmes' => ['B.Sc Mathematics', 'B.Sc Statistics']
                    ],
                    'Physics' => [
                        'code' => 'PHY',
                        'programmes' => ['B.Sc Physics', 'B.Sc Geophysics']
                    ],
                    'Chemistry' => [
                        'code' => 'CHM',
                        'programmes' => ['B.Sc Chemistry', 'B.Sc Industrial Chemistry']
                    ],
                    'Microbiology' => [
                        'code' => 'MCB',
                        'programmes' => ['B.Sc Microbiology']
                    ],
                ]
            ],
            'Faculty of Engineering' => [
                'code' => 'ENG',
                'departments' => [
                    'Electrical & Electronics Engineering' => [
                        'code' => 'EEE',
                        'programmes' => ['B.Eng Electrical & Electronics Engineering']
                    ],
                    'Mechanical Engineering' => [
                        'code' => 'MEE',
                        'programmes' => ['B.Eng Mechanical Engineering']
                    ],
                    'Civil Engineering' => [
                        'code' => 'CVE',
                        'programmes' => ['B.Eng Civil Engineering']
                    ],
                    'Petroleum Engineering' => [
                        'code' => 'PET',
                        'programmes' => ['B.Eng Petroleum Engineering']
                    ],
                ]
            ],
            'Faculty of Arts' => [
                'code' => 'ART',
                'departments' => [
                    'English & Literary Studies' => [
                        'code' => 'ELS',
                        'programmes' => ['B.A English Language', 'B.A Literature']
                    ],
                    'History & International Studies' => [
                        'code' => 'HIS',
                        'programmes' => ['B.A History & International Studies']
                    ],
                    'Linguistics' => [
                        'code' => 'LIN',
                        'programmes' => ['B.A Linguistics']
                    ],
                ]
            ],
            'Faculty of Management Sciences' => [
                'code' => 'MGT',
                'departments' => [
                    'Accounting' => [
                        'code' => 'ACC',
                        'programmes' => ['B.Sc Accounting']
                    ],
                    'Business Administration' => [
                        'code' => 'BUS',
                        'programmes' => ['B.Sc Business Administration']
                    ],
                    'Economics' => [
                        'code' => 'ECO',
                        'programmes' => ['B.Sc Economics']
                    ],
                ]
            ],
            'Faculty of Law' => [
                'code' => 'LAW',
                'departments' => [
                    'Private & Property Law' => [
                        'code' => 'PPL',
                        'programmes' => ['LL.B Law']
                    ],
                    'Public Law' => [
                        'code' => 'PUL',
                        'programmes' => [] // Sometimes Law is just one programme
                    ],
                ]
            ],
            'Faculty of Basic Medical Sciences' => [
                'code' => 'BMS',
                'departments' => [
                    'Anatomy' => [
                        'code' => 'ANA',
                        'programmes' => ['B.Sc Anatomy']
                    ],
                    'Physiology' => [
                        'code' => 'PHS',
                        'programmes' => ['B.Sc Physiology']
                    ],
                    'Medical Laboratory Science' => [
                        'code' => 'MLS',
                        'programmes' => ['B.MLS Medical Laboratory Science']
                    ],
                ]
            ],
            'Faculty of Social Sciences' => [
                'code' => 'SOS',
                'departments' => [
                    'Political Science' => [
                        'code' => 'POS',
                        'programmes' => ['B.Sc Political Science']
                    ],
                    'Sociology' => [
                        'code' => 'SOC',
                        'programmes' => ['B.Sc Sociology']
                    ],
                    'Mass Communication' => [
                        'code' => 'MAC',
                        'programmes' => ['B.Sc Mass Communication']
                    ],
                ]
            ],
        ];

        foreach ($faculties as $facultyName => $data) {
            $faculty = Faculty::firstOrCreate(
                ['code' => $data['code']],
                ['name' => $facultyName]
            );

            foreach ($data['departments'] as $deptName => $deptData) {
                $department = $faculty->departments()->firstOrCreate(
                    ['code' => $deptData['code']],
                    ['name' => $deptName]
                );

                foreach ($deptData['programmes'] as $progName) {
                    $department->programmes()->firstOrCreate(
                        ['name' => $progName],
                        ['type' => 'UG', 'is_active' => true]
                    );
                }

                // Seed some basic courses for each department (100 Level)
                $deptCode = $deptData['code'];
                $courses = [
                    ['code' => $deptCode . ' 101', 'title' => 'Intro to ' . $deptName . ' I', 'semester' => '1'],
                    ['code' => $deptCode . ' 102', 'title' => 'Intro to ' . $deptName . ' II', 'semester' => '2'],
                ];

                foreach ($courses as $c) {
                    Course::firstOrCreate(
                        ['code' => $c['code']],
                        [
                            'title' => $c['title'],
                            'units' => 2,
                            'department_id' => $department->id,
                            'level' => 100,
                            'semester' => $c['semester'],
                            'is_active' => true
                        ]
                    );
                }
            }
        }

        // General Courses (GNS) - assigned to a "General Studies" department usually, 
        // but for now we can assign to Sciences or create a GST Unit.
        // Let's create a 'General Studies' Faculty/Dept for clarity.

        $gstFaculty = Faculty::firstOrCreate(['code' => 'GST'], ['name' => 'General Studies Unit']);
        $gstDept = $gstFaculty->departments()->firstOrCreate(['code' => 'GST'], ['name' => 'General Studies']);

        $generalCourses = [
            ['code' => 'GNS 101', 'title' => 'Use of English I', 'semester' => '1', 'units' => 2],
            ['code' => 'GNS 102', 'title' => 'Use of English II', 'semester' => '2', 'units' => 2],
            ['code' => 'GNS 111', 'title' => 'Library Management', 'semester' => '1', 'units' => 1],
            ['code' => 'GNS 106', 'title' => 'Philosophy and Logic', 'semester' => '2', 'units' => 2],
        ];

        foreach ($generalCourses as $c) {
            Course::firstOrCreate(
                ['code' => $c['code']],
                [
                    'title' => $c['title'],
                    'units' => $c['units'],
                    'department_id' => $gstDept->id,
                    'level' => 100,
                    'semester' => $c['semester'],
                    'is_active' => true
                ]
            );
        }
    }
}
