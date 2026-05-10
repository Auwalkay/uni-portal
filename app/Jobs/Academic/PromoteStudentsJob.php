<?php

namespace App\Jobs\Academic;

use App\Models\Session;
use App\Models\Student;
use App\Models\Semester;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\AcademicCacheService;

class PromoteStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $session = Session::findOrFail($this->sessionId);

        // 1. Prepare Session and Semester Statuses
        DB::transaction(function () use ($session) {
            Session::where('is_current', true)->update(['is_current' => false]);
            $session->update(['is_current' => true]);

            Semester::query()->update(['is_current' => false]);
            $firstSemester = $session->semesters()->where('name', 'First Semester')->first();
            if ($firstSemester) {
                $firstSemester->update(['is_current' => true]);
            }
        });

        $firstSemesterName = $session->semesters()->where('name', 'First Semester')->value('name') ?? 'First Semester';

        // 2. Dispatch Individual Jobs for each student
        // Using chunkById to handle large datasets (5K+) without memory issues
        Student::where('status', '!=', 'graduated')
            ->chunkById(500, function ($students) use ($session, $firstSemesterName) {
                foreach ($students as $student) {
                    ProcessStudentSessionJob::dispatch($student, $session, $firstSemesterName);
                }
            });

        AcademicCacheService::clearAll();
    }
}
