<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Timetable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class TimetableImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function collection(Collection $rows)
    {
        $currentSession = Session::where('is_current', true)->first() ?? Session::latest('id')->first();
        if (!$currentSession) {
            $this->stats['errors'][] = "No active academic session found.";
            return;
        }

        $currentSemester = Semester::where('session_id', $currentSession->id)
            ->where('is_current', true)
            ->first() ?? Semester::where('session_id', $currentSession->id)->first();

        if (!$currentSemester) {
            $this->stats['errors'][] = "No active semester found for current session.";
            return;
        }

        // Cache all courses for fast lookup
        $allCourses = Course::all();

        foreach ($rows as $index => $row) {
            try {
                $rowArray = $row->toArray();

                // Flexible header keys
                $rawCourseCode = $rowArray['course_code'] ?? $rowArray['course'] ?? $rowArray['code'] ?? $rowArray['coursecode'] ?? null;
                $rawDay = $rowArray['day'] ?? $rowArray['day_of_week'] ?? null;
                $rawStartTime = $rowArray['start_time'] ?? $rowArray['start'] ?? $rowArray['from'] ?? null;
                $rawEndTime = $rowArray['end_time'] ?? $rowArray['end'] ?? $rowArray['to'] ?? null;
                $rawVenue = $rowArray['venue'] ?? $rowArray['room'] ?? $rowArray['hall'] ?? 'TBA';

                if (!$rawCourseCode || !$rawDay || !$rawStartTime || !$rawEndTime) {
                    $this->stats['skipped']++;
                    continue;
                }

                $rawCourseCode = trim((string)$rawCourseCode);
                $day = ucfirst(strtolower(trim((string)$rawDay)));
                $startTime = $this->formatTime($rawStartTime);
                $endTime = $this->formatTime($rawEndTime);

                if (!$startTime || !$endTime) {
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Invalid time format ($rawStartTime - $rawEndTime) for course '$rawCourseCode'.";
                    $this->stats['skipped']++;
                    continue;
                }

                // Smart Course Lookup (handles MIU- prefix, spacing, and clean matching)
                $course = $this->findCourse($rawCourseCode, $allCourses);

                if (!$course) {
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Course '$rawCourseCode' not found in database.";
                    $this->stats['skipped']++;
                    continue;
                }

                // Update or Create Timetable entry
                $record = Timetable::updateOrCreate(
                    [
                        'session_id' => $currentSession->id,
                        'semester_id' => $currentSemester->id,
                        'course_id' => $course->id,
                        'day' => $day,
                        'start_time' => $startTime,
                    ],
                    [
                        'end_time' => $endTime,
                        'venue' => trim((string)$rawVenue) ?: 'TBA',
                        'department_id' => $course->department_id,
                        'level' => $course->level,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $this->stats['created']++;
                } else {
                    $this->stats['updated']++;
                }

            } catch (\Exception $e) {
                $this->stats['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->stats['skipped']++;
                Log::error($e->getMessage());
            }
        }
    }

    /**
     * Smart Course matching logic (handles MIU- prefix, space variations, exact codes)
     */
    private function findCourse(string $rawCode, Collection $allCourses)
    {
        // 1. Exact match
        $found = $allCourses->first(fn($c) => strcasecmp($c->code, $rawCode) === 0);
        if ($found) return $found;

        // 2. Clean code without spaces/hyphens
        $cleanRaw = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $rawCode));
        $found = $allCourses->first(fn($c) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $c->code)) === $cleanRaw);
        if ($found) return $found;

        // 3. Strip 'MIU-' or 'MIU - ' prefix (e.g. 'MIU-SOC 313' -> 'SOC 313')
        $strippedPrefix = preg_replace('/^MIU\s*-\s*/i', '', $rawCode);
        if ($strippedPrefix !== $rawCode) {
            $found = $allCourses->first(fn($c) => strcasecmp($c->code, $strippedPrefix) === 0);
            if ($found) return $found;

            $cleanStripped = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $strippedPrefix));
            $found = $allCourses->first(fn($c) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $c->code)) === $cleanStripped);
            if ($found) return $found;
        }

        // 4. Format with space (e.g. SOC103 -> SOC 103)
        if (preg_match('/^([A-Z\-]+)(\d+.*)$/i', $cleanRaw, $matches)) {
            $formatted = $matches[1] . ' ' . $matches[2];
            $found = $allCourses->first(fn($c) => strcasecmp($c->code, $formatted) === 0);
            if ($found) return $found;
        }

        return null;
    }

    /**
     * Format time string or Excel numeric serial time to HH:MM
     */
    private function formatTime($time)
    {
        if (!$time) return null;

        if (is_numeric($time)) {
            // Excel time serial (fraction of 24h day)
            $totalSeconds = round((float)$time * 86400);
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            return sprintf('%02d:%02d', $hours, $minutes);
        }

        $timestamp = strtotime(trim((string)$time));
        if ($timestamp === false) return null;

        return date('H:i', $timestamp);
    }

    public function getStats()
    {
        return $this->stats;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
