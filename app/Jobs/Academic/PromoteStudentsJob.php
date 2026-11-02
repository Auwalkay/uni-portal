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
        $stream = $session->academic_stream ?? 'UG';
        $calendarType = $session->calendar_type ?? 'semester';

        // 1. Prepare Session and Semester Statuses scoped to the target stream & calendar type
        DB::transaction(function () use ($session, $stream, $calendarType) {
            $sameStreamSessionIds = Session::where('academic_stream', $stream)
                ->where('calendar_type', $calendarType)
                ->pluck('id');

            Session::whereIn('id', $sameStreamSessionIds)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $session->update(['is_current' => true]);

            Semester::whereIn('session_id', $sameStreamSessionIds)->update(['is_current' => false]);

            $firstSemester = $session->semesters()->where('name', 'First Semester')->first()
                ?? $session->semesters()->where('name', 'Summer Semester')->first()
                ?? $session->semesters()->first();
            if ($firstSemester) {
                $firstSemester->update(['is_current' => true]);
            }
        });

        $firstSemesterName = $session->semesters()->where('name', 'First Semester')->value('name')
            ?? $session->semesters()->where('name', 'Summer Semester')->value('name')
            ?? $session->semesters()->value('name')
            ?? 'First Semester';

        // 2. Dispatch Individual Jobs only for students belonging to this academic stream & calendar type
        Student::where('status', '!=', 'graduated')
            ->where(function ($query) use ($stream, $calendarType) {
                $query->whereHas('program', function ($pq) use ($stream, $calendarType) {
                    $pq->where('academic_stream', $stream)
                        ->where('calendar_type', $calendarType);
                });

                if ($stream === 'UG' && $calendarType === 'semester') {
                    $query->orWhereNull('program_id');
                }
            })
            ->chunkById(500, function ($students) use ($session, $firstSemesterName) {
                foreach ($students as $student) {
                    ProcessStudentSessionJob::dispatch($student, $session, $firstSemesterName);
                }
            });

        AcademicCacheService::clearAll();
    }
}
