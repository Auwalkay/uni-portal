<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeConfiguration;
use App\Models\FeeType;
use App\Models\Programme;
use App\Models\Session;
use App\Services\AcademicCacheService;
use Illuminate\Database\Seeder;

class MiuPostgraduateProgrammesAndFeesSeeder extends Seeder
{
    /**
     * Exact MIU Nigeria Postgraduate Programmes & Tuition Fees schedule.
     */
    public function run(): void
    {
        $catalog = [
            // Management & Social Sciences (200k / 250k / 350k)
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'PGD Accounting', 'type' => 'PGD', 'duration' => 1, 'fee' => 200000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'M.Sc. Accounting', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'Ph.D. Accounting', 'type' => 'PHD', 'duration' => 3, 'fee' => 350000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'MIPSA - Master in Public Sector Accounting', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'MIPSA - Master in Public Audit Management', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'MIPSA - Master in Tax Management', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'name' => 'MIPSA - Master in Treasury Management', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],

            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ECO', 'dept_name' => 'Economics', 'name' => 'PGD Economics', 'type' => 'PGD', 'duration' => 1, 'fee' => 200000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ECO', 'dept_name' => 'Economics', 'name' => 'M.Sc. Economics', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ECO', 'dept_name' => 'Economics', 'name' => 'Ph.D. Economics', 'type' => 'PHD', 'duration' => 3, 'fee' => 350000],

            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ENT', 'dept_name' => 'Entrepreneurship', 'name' => 'PGD Entrepreneurship', 'type' => 'PGD', 'duration' => 1, 'fee' => 200000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ENT', 'dept_name' => 'Entrepreneurship', 'name' => 'M.Sc. Entrepreneurship', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'ENT', 'dept_name' => 'Entrepreneurship', 'name' => 'Ph.D. Entrepreneurship', 'type' => 'PHD', 'duration' => 3, 'fee' => 350000],

            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'FIN', 'dept_name' => 'Finance', 'name' => 'PGD Finance', 'type' => 'PGD', 'duration' => 1, 'fee' => 200000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'FIN', 'dept_name' => 'Finance', 'name' => 'M.Sc. Finance', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 250000],
            ['fac_code' => 'MGT', 'fac_name' => 'Faculty of Management Sciences', 'dept_code' => 'FIN', 'dept_name' => 'Finance', 'name' => 'Ph.D. Finance', 'type' => 'PHD', 'duration' => 3, 'fee' => 350000],

            // Computing & IT (300k / 350k / 450k)
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CSC', 'dept_name' => 'Computer Science', 'name' => 'PGD Computer Science', 'type' => 'PGD', 'duration' => 1, 'fee' => 300000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CSC', 'dept_name' => 'Computer Science', 'name' => 'M.Sc. Computer Science', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 350000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CSC', 'dept_name' => 'Computer Science', 'name' => 'Ph.D. Computer Science', 'type' => 'PHD', 'duration' => 3, 'fee' => 450000],

            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CYB', 'dept_name' => 'Cyber Security', 'name' => 'PGD Cyber Security', 'type' => 'PGD', 'duration' => 1, 'fee' => 300000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CYB', 'dept_name' => 'Cyber Security', 'name' => 'M.Sc. Cyber Security', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 350000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CYB', 'dept_name' => 'Cyber Security', 'name' => 'Ph.D. Cyber Security', 'type' => 'PHD', 'duration' => 3, 'fee' => 450000],

            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'SWE', 'dept_name' => 'Software Engineering', 'name' => 'PGD Software Engineering', 'type' => 'PGD', 'duration' => 1, 'fee' => 300000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'SWE', 'dept_name' => 'Software Engineering', 'name' => 'M.Sc. Software Engineering', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 350000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'SWE', 'dept_name' => 'Software Engineering', 'name' => 'Ph.D. Software Engineering', 'type' => 'PHD', 'duration' => 3, 'fee' => 450000],

            // Pure & Applied Sciences (255k / 300k / 400k)
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'BTC', 'dept_name' => 'Biotechnology', 'name' => 'PGD Biotechnology', 'type' => 'PGD', 'duration' => 1, 'fee' => 255000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'BTC', 'dept_name' => 'Biotechnology', 'name' => 'M.Sc. Biotechnology', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 300000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'BTC', 'dept_name' => 'Biotechnology', 'name' => 'Ph.D. Biotechnology', 'type' => 'PHD', 'duration' => 3, 'fee' => 400000],

            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CHM', 'dept_name' => 'Chemistry', 'name' => 'PGD Industrial Chemistry', 'type' => 'PGD', 'duration' => 1, 'fee' => 255000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CHM', 'dept_name' => 'Chemistry', 'name' => 'M.Sc. Industrial Chemistry', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 300000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'CHM', 'dept_name' => 'Chemistry', 'name' => 'Ph.D. Industrial Chemistry', 'type' => 'PHD', 'duration' => 3, 'fee' => 400000],

            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'PHY', 'dept_name' => 'Physics', 'name' => 'PGD Physics with Electronics', 'type' => 'PGD', 'duration' => 1, 'fee' => 255000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'PHY', 'dept_name' => 'Physics', 'name' => 'M.Sc. Physics with Electronics', 'type' => 'MSC', 'duration' => 1.5, 'fee' => 300000],
            ['fac_code' => 'SCI', 'fac_name' => 'Faculty of Sciences', 'dept_code' => 'PHY', 'dept_name' => 'Physics', 'name' => 'Ph.D. Physics with Electronics', 'type' => 'PHD', 'duration' => 3, 'fee' => 400000],
        ];

        $tuitionFeeType = FeeType::firstOrCreate(
            ['slug' => 'tuition-fee'],
            ['name' => 'Tuition Fee', 'description' => 'Tuition fee for the session', 'is_one_time' => false]
        );

        $enrolmentFeeType = FeeType::firstOrCreate(
            ['slug' => 'pg-application-enrolment-fee'],
            ['name' => 'Application / Enrolment Fee', 'description' => 'One-time Postgraduate application/enrolment fee', 'is_one_time' => true]
        );

        $pgSessions = Session::where('academic_stream', 'PG')->get();
        if ($pgSessions->isEmpty()) {
            $pgSessions = Session::where('is_current', true)->get();
        }

        foreach ($catalog as $row) {
            $faculty = Faculty::firstOrCreate(
                ['code' => $row['fac_code']],
                ['name' => $row['fac_name'], 'is_active' => true]
            );

            $department = Department::firstOrCreate(
                ['code' => $row['dept_code']],
                [
                    'name' => $row['dept_name'],
                    'faculty_id' => $faculty->id,
                    'is_academic' => true,
                    'is_active' => true,
                ]
            );

            $matricFormat = match ($row['type']) {
                'PGD' => 'PGD/{YEAR}/{SEQUENCE}',
                'PHD' => 'PHD/{YEAR}/{SEQUENCE}',
                default => 'MSC/{YEAR}/{SEQUENCE}',
            };

            $programme = Programme::updateOrCreate(
                ['name' => $row['name']],
                [
                    'department_id' => $department->id,
                    'type' => $row['type'],
                    'academic_stream' => 'PG',
                    'calendar_type' => 'semester',
                    'duration' => $row['duration'],
                    'matric_format' => $matricFormat,
                    'min_graduation_cgpa' => 3.00,
                    'billing_model' => 'composite_term',
                    'is_active' => true,
                ]
            );

            foreach ($pgSessions as $session) {
                FeeConfiguration::updateOrCreate(
                    [
                        'fee_type_id' => $tuitionFeeType->id,
                        'session_id' => $session->id,
                        'program_id' => $programme->id,
                    ],
                    [
                        'faculty_id' => $faculty->id,
                        'department_id' => $department->id,
                        'level' => null,
                        'entry_mode' => $row['type'],
                        'amount' => $row['fee'],
                        'is_compulsory' => true,
                        'is_per_course' => false,
                        'is_milestone_fee' => false,
                    ]
                );
            }
        }

        // One-time ₦50,000 Application / Enrolment Fee for all PG sessions
        foreach ($pgSessions as $session) {
            FeeConfiguration::updateOrCreate(
                [
                    'fee_type_id' => $enrolmentFeeType->id,
                    'session_id' => $session->id,
                    'program_id' => null,
                    'entry_mode' => 'Postgraduate',
                ],
                [
                    'faculty_id' => null,
                    'department_id' => null,
                    'level' => null,
                    'amount' => 50000,
                    'is_compulsory' => true,
                    'is_per_course' => false,
                    'is_milestone_fee' => false,
                ]
            );
        }

        AcademicCacheService::clearAll();
        $this->command?->info('Seeded 30 MIU Postgraduate programmes, tuition fees, and ₦50,000 one-time enrolment fee.');
    }
}
