<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\Session;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class CourseAllocationImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private $stats = [
        'created' => 0,
        'skipped' => 0,
        'duplicates' => 0,
        'errors' => []
    ];

    public function collection(Collection $rows)
    {
        $currentSession = Session::where('is_current', true)->first() ?? Session::latest('id')->first();
        if (!$currentSession) {
            $this->stats['errors'][] = "No active session found.";
            return;
        }

        // Cache all courses and staff for fast lookup
        $allCourses = Course::all();
        $allStaff = Staff::with('user')->get();

        foreach ($rows as $index => $row) {
            try {
                $rowArray = $row->toArray();

                // Flexible header detection for Course Code
                $rawCourseCode = $rowArray['course_code'] ?? $rowArray['course'] ?? $rowArray['code'] ?? $rowArray['coursecode'] ?? null;
                
                // Flexible header detection for Staff Number (supports ERP_staff_number, staff_number, etc.)
                $rawStaffNumber = $rowArray['erp_staff_number'] ?? $rowArray['staff_number'] ?? $rowArray['erp_staff_no'] ?? $rowArray['staff_no'] ?? $rowArray['staff_id'] ?? $rowArray['staff'] ?? $rowArray['staffnumber'] ?? null;
                
                // Flexible header detection for Staff Name
                $rawStaffName = $rowArray['staff_name'] ?? $rowArray['name'] ?? $rowArray['staffname'] ?? null;

                if (!$rawCourseCode) {
                    $this->stats['skipped']++;
                    continue;
                }

                $rawCourseCode = trim((string)$rawCourseCode);
                $rawStaffNumber = $rawStaffNumber ? trim((string)$rawStaffNumber) : null;
                $rawStaffName = $rawStaffName ? trim((string)$rawStaffName) : null;

                if (!$rawStaffNumber && !$rawStaffName) {
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Missing both staff number and staff name for course '$rawCourseCode'.";
                    $this->stats['skipped']++;
                    continue;
                }

                // 1. Find Course (with flexible prefix & format matching)
                $course = $this->findCourse($rawCourseCode, $allCourses);

                if (!$course) {
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Course '$rawCourseCode' not found in database.";
                    $this->stats['skipped']++;
                    continue;
                }

                // 2. Find Staff (by staff number, numeric part, or staff name)
                $staff = $this->findStaff($rawStaffNumber, $rawStaffName, $allStaff);

                if (!$staff) {
                    $staffIdentifier = $rawStaffNumber ?: $rawStaffName;
                    $this->stats['errors'][] = "Row " . ($index + 2) . ": Staff '$staffIdentifier' not found in database.";
                    $this->stats['skipped']++;
                    continue;
                }

                // 3. Check for existing allocation in current session
                $exists = CourseAllocation::where('course_id', $course->id)
                    ->where('staff_id', $staff->id)
                    ->where('session_id', $currentSession->id)
                    ->exists();

                if ($exists) {
                    $this->stats['duplicates']++;
                    $this->stats['skipped']++;
                    continue;
                }

                // 4. Create Allocation
                CourseAllocation::create([
                    'course_id' => $course->id,
                    'staff_id' => $staff->id,
                    'session_id' => $currentSession->id,
                    'is_primary' => true
                ]);

                $this->stats['created']++;

            } catch (\Exception $e) {
                $this->stats['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->stats['skipped']++;
                Log::error($e->getMessage());
            }
        }
    }

    /**
     * Smart Course matching logic
     */
    private function findCourse(string $rawCode, Collection $allCourses)
    {
        // 1. Exact match
        $found = $allCourses->first(fn($c) => strcasecmp($c->code, $rawCode) === 0);
        if ($found) return $found;

        // 2. Cleaned code without extra spaces/hyphens
        $cleanRaw = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $rawCode));
        $found = $allCourses->first(fn($c) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $c->code)) === $cleanRaw);
        if ($found) return $found;

        // 3. Strip 'MIU-' or 'MIU - ' prefix (e.g. 'MIU-SEN 103' -> 'SEN 103')
        $strippedPrefix = preg_replace('/^MIU\s*-\s*/i', '', $rawCode);
        if ($strippedPrefix !== $rawCode) {
            $found = $allCourses->first(fn($c) => strcasecmp($c->code, $strippedPrefix) === 0);
            if ($found) return $found;

            $cleanStripped = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $strippedPrefix));
            $found = $allCourses->first(fn($c) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $c->code)) === $cleanStripped);
            if ($found) return $found;
        }

        // 4. Format with space (e.g. COS201 -> COS 201)
        if (preg_match('/^([A-Z\-]+)(\d+.*)$/i', $cleanRaw, $matches)) {
            $formatted = $matches[1] . ' ' . $matches[2];
            $found = $allCourses->first(fn($c) => strcasecmp($c->code, $formatted) === 0);
            if ($found) return $found;
        }

        return null;
    }

    /**
     * Smart Staff matching logic
     */
    private function findStaff(?string $staffNumber, ?string $staffName, Collection $allStaff)
    {
        if ($staffNumber) {
            // Exact staff_number match (e.g. MIUERP09305)
            $found = $allStaff->first(fn($s) => strcasecmp($s->staff_number, $staffNumber) === 0);
            if ($found) return $found;

            // Numeric part match (e.g. 09305 / 9305)
            $numOnly = preg_replace('/[^0-9]/', '', $staffNumber);
            if ($numOnly) {
                $found = $allStaff->first(function ($s) use ($numOnly) {
                    $sNum = preg_replace('/[^0-9]/', '', $s->staff_number);
                    return $sNum && (ltrim($sNum, '0') === ltrim($numOnly, '0'));
                });
                if ($found) return $found;
            }
        }

        if ($staffName) {
            // Strip title prefixes like MR, MRS, DR, PROF, MR.
            $cleanName = trim(preg_replace('/^(MR|MRS|MS|DR|PROF|ENG|ENGR)\.?\s+/i', '', $staffName));
            
            // Match against associated User name
            $found = $allStaff->first(function ($s) use ($cleanName) {
                if (!$s->user) return false;
                $userName = trim(preg_replace('/^(MR|MRS|MS|DR|PROF|ENG|ENGR)\.?\s+/i', '', $s->user->name));
                return strcasecmp($userName, $cleanName) === 0 || str_contains(strtolower($userName), strtolower($cleanName)) || str_contains(strtolower($cleanName), strtolower($userName));
            });

            if ($found) return $found;
        }

        return null;
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
