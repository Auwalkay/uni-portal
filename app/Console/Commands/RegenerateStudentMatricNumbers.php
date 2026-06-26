<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Helpers\MatriculationNumberHelper;
use Illuminate\Support\Facades\DB;

class RegenerateStudentMatricNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:regenerate-matric-numbers {--dry-run : Print changes without saving to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate matriculation numbers for all existing students using the current format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $students = Student::with(['admittedSession', 'department', 'faculty', 'program', 'user'])
            ->orderBy('admitted_session_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($students->isEmpty()) {
            $this->info('No students found in the database.');
            return 0;
        }

        $this->info('Found ' . $students->count() . ' students to process.');

        if ($dryRun) {
            $this->comment('*** DRY RUN MODE - NO CHANGES WILL BE SAVED ***');
        }

        DB::transaction(function () use ($students, $dryRun) {
            // Extract and preserve the original serials for all students first
            $originalSerials = [];
            foreach ($students as $student) {
                $originalMatric = $student->matriculation_number;
                $originalSerial = '001'; // Fallback
                if ($originalMatric && preg_match('/(\d{3,4})$/', $originalMatric, $matches)) {
                    $originalSerial = substr($matches[1], -3);
                }
                $originalSerials[$student->id] = $originalSerial;
            }

            // 1. First, set all student matriculation numbers to temporary values to prevent sequence collisions during generation
            if (!$dryRun) {
                $this->info('Temporarily clearing existing matriculation numbers to prevent database unique key conflicts...');
                foreach ($students as $student) {
                    $student->updateQuietly([
                        'matriculation_number' => 'TEMP-' . strtoupper(uniqid())
                    ]);
                }
            }

            // 2. Now, generate the new matriculation numbers in order
            $this->info('Generating and assigning new matriculation numbers preserving their original serials...');
            
            $updates = [];
            foreach ($students as $index => $student) {
                // Determine the 2-digit year from the admitted session name (e.g. "2024/2025" -> "24")
                $year = date('y');
                if ($student->admittedSession && preg_match('/^(\d{4})/', $student->admittedSession->name, $matches)) {
                    $year = substr($matches[1], -2);
                } else {
                    // Fallback to student creation year
                    $year = $student->created_at ? $student->created_at->format('y') : date('y');
                }

                $deptCode = $student->department ? $student->department->code : 'GEN';
                $facCode = $student->faculty ? $student->faculty->code : 'GEN';
                
                // Determine the entry level / current level digit
                $level = $student->current_level ?: '100';
                $startDigit = substr(trim($level), 0, 1);
                if (!in_array($startDigit, ['1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                    $startDigit = '1';
                }

                // Retrieve the preserved original serial and prepend the level start digit
                $originalSerial = $originalSerials[$student->id];
                $newSequence = $startDigit . $originalSerial;

                // Generate new matric number using the forced sequence
                $newMatric = MatriculationNumberHelper::generate([
                    'year' => $year,
                    'dept_code' => $deptCode,
                    'fac_code' => $facCode,
                    'level' => $level,
                    'sequence' => $newSequence,
                ]);

                if (!$dryRun) {
                    $student->updateQuietly([
                        'matriculation_number' => $newMatric
                    ]);
                }

                $updates[] = [
                    'name' => $student->user ? $student->user->name : 'Unknown Student',
                    'session' => $student->admittedSession ? $student->admittedSession->name : 'N/A',
                    'level' => $level,
                    'new_matric' => $newMatric,
                ];
            }

            // Print the summary table
            $headers = ['Student Name', 'Admitted Session', 'Level', 'New Matric Number'];
            $this->table($headers, $updates);

            if (!$dryRun) {
                $this->info('Successfully regenerated matriculation numbers for ' . count($updates) . ' students.');
            }
        });

        return 0;
    }
}
