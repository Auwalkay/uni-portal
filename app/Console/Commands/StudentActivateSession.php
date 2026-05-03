<?php

namespace App\Console\Commands;

use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\AcademicCacheService;

class StudentActivateSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'student:activate-session {sessionId : The ID of the academic session to activate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate an academic session, promote students to next level, and handle graduation.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $sessionId = $this->argument('sessionId');
        $session = Session::findOrFail($sessionId);

        DB::transaction(function () use ($session) {
            // Deactivate other sessions
            Session::where('is_current', true)->update(['is_current' => false]);

            // Activate target session
            $session->update(['is_current' => true]);

            // Activate first semester of this session
            $firstSemester = $session->semesters()->where('name', 'First Semester')->first();

            if ($firstSemester) {
                // Deactivate all semesters globally
                \App\Models\Semester::query()->update(['is_current' => false]);
                $firstSemester->update(['is_current' => true]);
            }

            Student::chunkById(200, function ($students) use ($session, $firstSemester) {
                foreach ($students as $student) {
                    $existingSession = StudentSession::where('student_id', $student->id)
                        ->where('session_id', $session->id)
                        ->first();

                    if ($existingSession) {
                        // Reverting or already attended session: no promotion.
                        $existingSession->update(['status' => 'active']);

                        $student->update([
                            'current_level' => $existingSession->level,
                            'status' => 'active',
                        ]);
                    } else {
                        // Promote if regular session and not already graduated
                        if ($student->status === 'graduated') {
                            continue;
                        }

                        $newLevel = $student->current_level;
                        // $currenSemester = $session->semesters()->where('is_current', true)->first();

                        $isGraduating = false;

                        if ($session->type === 'regular') {
                            $maxLevel = $student->program_duration * 100;

                            if ($student->current_level >= $maxLevel) {
                                $isGraduating = true;
                                $student->update([
                                    'status' => 'graduated',
                                    'graduated_at' => now(),
                                ]);
                            } else {
                                $newLevel += 100;
                                $student->update([
                                    'current_level' => $newLevel,
                                    'status' => 'active'
                                ]);
                            }
                        }

                        // Create StudentSession entry for the new academic session,
                        // unless they are graduating (graduated students don't need active sessions)
                        if (!$isGraduating) {
                            StudentSession::create([
                                'student_id' => $student->id,
                                'session_id' => $session->id,
                                'level' => $newLevel,
                                'status' => 'active',
                                'semester' => $firstSemester->name,
                            ]);
                        }
                    }
                }
            });
        });

        AcademicCacheService::clearAll();
        $this->info('Session activated and students promoted/graduated successfully.');
    }
}
