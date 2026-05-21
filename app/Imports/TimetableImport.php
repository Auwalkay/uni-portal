<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Timetable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class TimetableImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $currentSession = Session::current();
        if (!$currentSession) {
            throw new \Exception("No active session found.");
        }

        $currentSemester = Semester::where('session_id', $currentSession->id)
            ->where('is_current', true)
            ->first();

        if (!$currentSemester) {
            throw new \Exception("No active semester found for the current session.");
        }

        foreach ($rows as $row) {
            if (!isset($row['course_code']) || !isset($row['day']) || !isset($row['start_time']) || !isset($row['end_time'])) {
                continue; // Skip invalid rows
            }

            $course = Course::where('code', trim($row['course_code']))->first();

            if ($course) {
                Timetable::updateOrCreate(
                    [
                        'session_id' => $currentSession->id,
                        'semester_id' => $currentSemester->id,
                        'course_id' => $course->id,
                        'day' => ucfirst(strtolower(trim($row['day']))),
                        'start_time' => $this->formatTime($row['start_time']),
                    ],
                    [
                        'end_time' => $this->formatTime($row['end_time']),
                        'venue' => $row['venue'] ?? 'TBA',
                        'department_id' => $course->department_id,
                        'level' => $course->level,
                    ]
                );
            } else {
                Log::warning("Course not found for timetable import: " . $row['course_code']);
            }
        }
    }

    private function formatTime($time)
    {
        // Handle Excel time serial or string 
        // Simple string handling for now (HH:MM)
        return date('H:i', strtotime($time));
    }
}
