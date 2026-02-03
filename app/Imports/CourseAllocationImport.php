<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\Session;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class CourseAllocationImport implements ToCollection, WithHeadingRow
{
    private $stats = [
        'created' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function collection(Collection $rows)
    {
        $currentSession = Session::where('is_current', true)->first();
        if (!$currentSession) {
            $this->stats['errors'][] = "No active session found.";
            return;
        }

        foreach ($rows as $index => $row) {
            try {
                // Required fields
                if (!isset($row['course_code']) || !isset($row['staff_email'])) {
                    $this->stats['skipped']++;
                    continue;
                }

                $courseCode = trim($row['course_code']);
                $staffEmail = trim($row['staff_email']);

                // Find Course
                $course = Course::where('code', $courseCode)->first();
                if (!$course) {
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Course '$courseCode' not found.";
                    $this->stats['skipped']++;
                    continue;
                }

                // Find Staff
                $user = User::where('email', $staffEmail)->first();
                if (!$user || !$user->staff) {
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Staff with email '$staffEmail' not found.";
                    $this->stats['skipped']++;
                    continue;
                }

                $staff = $user->staff;

                // Check for existing allocation
                $exists = CourseAllocation::where('course_id', $course->id)
                    ->where('staff_id', $staff->id)
                    ->where('session_id', $currentSession->id)
                    ->exists();

                if ($exists) {
                    $this->stats['skipped']++;
                    continue;
                }

                // Create Allocation
                CourseAllocation::create([
                    'course_id' => $course->id,
                    'staff_id' => $staff->id,
                    'session_id' => $currentSession->id,
                    'is_primary' => true // Default to true for bulk import? Or maybe assume primary if first.
                ]);

                $this->stats['created']++;

            } catch (\Exception $e) {
                $this->stats['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->stats['skipped']++;
                Log::error($e->getMessage());
            }
        }
    }

    public function getStats()
    {
        return $this->stats;
    }
}
