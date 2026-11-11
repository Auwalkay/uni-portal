<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeConfiguration;
use App\Models\FeeType;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Console\Command;

class SeedPostgraduateFeeSchedule extends Command
{
    protected $signature = 'fees:seed-pg-schedule {session_id? : The academic session ID}';
    protected $description = 'Seed or update the tier-based Postgraduate tuition fee rules for a session';

    /**
     * Tier-Based Official PG Fee Schedule (Hierarchical Faculty & Department Tiers)
     */
    public const TIER_RULES = [
        // Faculty of Management Sciences (PGD: 200k, M.Sc: 250k, MBA: 400k, Ph.D: 350k)
        ['fac_code' => 'MGT', 'tier' => 'PGD', 'amount' => 200000],
        ['fac_code' => 'MGT', 'tier' => 'MSc', 'amount' => 250000],
        ['fac_code' => 'MGT', 'tier' => 'MBA', 'amount' => 400000],
        ['fac_code' => 'MGT', 'tier' => 'PhD', 'amount' => 350000],

        // Faculty of Social Sciences (PGD: 200k, M.Sc: 250k, Ph.D: 350k)
        ['fac_code' => 'SOS', 'tier' => 'PGD', 'amount' => 200000],
        ['fac_code' => 'SOS', 'tier' => 'MSc', 'amount' => 250000],
        ['fac_code' => 'SOS', 'tier' => 'PhD', 'amount' => 350000],

        // Faculty of Sciences - Pure & Applied Science Defaults (PGD: 255k, M.Sc: 300k, Ph.D: 400k)
        ['fac_code' => 'SCI', 'tier' => 'PGD', 'amount' => 255000],
        ['fac_code' => 'SCI', 'tier' => 'MSc', 'amount' => 300000],
        ['fac_code' => 'SCI', 'tier' => 'PhD', 'amount' => 400000],

        // Department of Computer Science (IT & Computing Tier: PGD: 300k, M.Sc: 350k, Ph.D: 450k)
        ['fac_code' => 'SCI', 'dept_code' => 'CSC', 'tier' => 'PGD', 'amount' => 300000],
        ['fac_code' => 'SCI', 'dept_code' => 'CSC', 'tier' => 'MSc', 'amount' => 350000],
        ['fac_code' => 'SCI', 'dept_code' => 'CSC', 'tier' => 'PhD', 'amount' => 450000],
    ];

    public function handle(): int
    {
        $sessionId = $this->argument('session_id');
        $sessions = $sessionId ? Session::where('id', $sessionId)->get() : Session::all();

        if ($sessions->isEmpty()) {
            $this->error('No academic session found.');
            return 1;
        }

        $tuitionFeeType = FeeType::where('slug', 'tuition-fee')->orWhere('name', 'Tuition Fee')->first();
        if (! $tuitionFeeType) {
            $tuitionFeeType = FeeType::create([
                'name' => 'Tuition Fee',
                'slug' => 'tuition-fee',
                'description' => 'Tuition fee for the session',
                'is_one_time' => false,
            ]);
        }

        // Ensure baseline sample programmes exist for testing/fresh DB
        $standardPrograms = [
            ['name' => 'M.Sc Computer Science', 'type' => 'MSC', 'dept_code' => 'CSC', 'dept_name' => 'Computer Science', 'fac_code' => 'SCI'],
            ['name' => 'Ph.D Cyber Security', 'type' => 'PHD', 'dept_code' => 'CSC', 'dept_name' => 'Computer Science', 'fac_code' => 'SCI'],
            ['name' => 'PGD Accounting', 'type' => 'PGD', 'dept_code' => 'ACC', 'dept_name' => 'Accounting', 'fac_code' => 'MGT'],
        ];

        foreach ($standardPrograms as $pItem) {
            $facName = $pItem['fac_code'] === 'SCI' ? 'Faculty of Sciences' : 'Faculty of Management Sciences';
            $pFac = Faculty::firstOrCreate(['code' => $pItem['fac_code']], ['name' => $facName, 'is_active' => true]);
            $pDept = Department::firstOrCreate(['code' => $pItem['dept_code']], ['name' => $pItem['dept_name'], 'faculty_id' => $pFac->id, 'is_academic' => true, 'is_active' => true]);
            Programme::firstOrCreate(
                ['name' => $pItem['name']],
                [
                    'department_id' => $pDept->id,
                    'type' => $pItem['type'],
                    'academic_stream' => 'PG',
                    'is_active' => true,
                ]
            );
        }

        $synced = 0;

        foreach ($sessions as $session) {
            foreach (static::TIER_RULES as $rule) {
                $facultyName = match ($rule['fac_code']) {
                    'MGT' => 'Faculty of Management Sciences',
                    'SOS' => 'Faculty of Social Sciences',
                    'SCI' => 'Faculty of Sciences',
                    default => 'Faculty of Sciences',
                };
                $faculty = Faculty::firstOrCreate(['code' => $rule['fac_code']], ['name' => $facultyName, 'is_active' => true]);

                $departmentId = null;
                if (! empty($rule['dept_code'])) {
                    $deptName = $rule['dept_code'] === 'CSC' ? 'Computer Science' : $rule['dept_code'];
                    $department = Department::firstOrCreate(
                        ['code' => $rule['dept_code']],
                        ['name' => $deptName, 'faculty_id' => $faculty->id, 'is_academic' => true, 'is_active' => true]
                    );
                    $departmentId = $department->id;
                }

                // Provision the Tier-Wide Fee Configuration rule (program_id = null)
                FeeConfiguration::updateOrCreate(
                    [
                        'fee_type_id' => $tuitionFeeType->id,
                        'session_id' => $session->id,
                        'faculty_id' => $faculty->id,
                        'department_id' => $departmentId,
                        'program_id' => null, // Tier-wide rule! Applies to all matching courses
                        'entry_mode' => $rule['tier'],
                    ],
                    [
                        'level' => null, // Program-wide across all years
                        'amount' => $rule['amount'],
                        'is_compulsory' => true,
                        'is_per_course' => false,
                        'is_milestone_fee' => false,
                    ]
                );

                $synced++;
            }
        }

        $this->info("Successfully synced {$synced} tier-based postgraduate fee rules.");
        return 0;
    }
}
