<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Programme;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgrammeCourseImport implements ToCollection, WithHeadingRow
{
    private $programme;
    
    private $stats = [
        'created' => 0,
        'linked' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function __construct(Programme $programme)
    {
        $this->programme = $programme;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $rowNum = $index + 2; // +1 for 0-index, +1 for heading row

                // Get row values (supporting multiple common variations of headers)
                $codeRaw = $row['course_code'] ?? $row['code'] ?? null;
                $titleRaw = $row['course_title'] ?? $row['title'] ?? null;
                $unitsRaw = $row['units'] ?? $row['credit_units'] ?? null;
                $levelRaw = $row['level'] ?? null;
                $semesterRaw = $row['semester'] ?? null;
                $compulsoryRaw = $row['is_compulsory'] ?? $row['type'] ?? $row['compulsory'] ?? null;

                // Skip completely empty rows
                if (empty($codeRaw) && empty($titleRaw)) {
                    continue;
                }

                $code = strtoupper(trim((string)$codeRaw));
                $title = trim((string)$titleRaw);
                $units = empty($unitsRaw) ? 3 : intval($unitsRaw);
                $level = empty($levelRaw) ? 100 : intval($levelRaw);

                if (empty($code)) {
                    $this->stats['errors'][] = "Row {$rowNum}: Course code is required.";
                    $this->stats['skipped']++;
                    continue;
                }

                if (empty($title)) {
                    $this->stats['errors'][] = "Row {$rowNum}: Course title is required for code '$code'.";
                    $this->stats['skipped']++;
                    continue;
                }

                // Map semester
                $semString = strtolower(trim((string)$semesterRaw));
                $semester = '1';
                if ($semString === '2' || str_contains($semString, 'second') || str_contains($semString, '2nd')) {
                    $semester = '2';
                }

                // Map is_compulsory (core vs elective)
                $compString = strtolower(trim((string)$compulsoryRaw));
                $isCompulsory = false;
                if (
                    $compString === '1' || 
                    $compString === 'true' || 
                    $compString === 'yes' || 
                    str_contains($compString, 'core') || 
                    str_contains($compString, 'compulsory')
                ) {
                    $isCompulsory = true;
                }

                DB::transaction(function () use ($code, $title, $units, $level, $semester, $isCompulsory) {
                    // Find or create global course record
                    $course = Course::where('code', $code)->first();
                    
                    if (!$course) {
                        $course = Course::create([
                            'id' => Str::uuid()->toString(),
                            'code' => $code,
                            'title' => $title,
                            'units' => $units,
                            'level' => $level,
                            'semester' => $semester,
                            'department_id' => $this->programme->department_id,
                            'is_active' => true,
                        ]);
                        $this->stats['created']++;
                    }

                    // Pivot linking
                    $exists = $this->programme->courses()->where('course_id', $course->id)->exists();
                    if (!$exists) {
                        $this->programme->courses()->attach($course->id, [
                            'id' => Str::uuid()->toString(),
                            'is_compulsory' => $isCompulsory,
                        ]);
                        $this->stats['linked']++;
                    } else {
                        // Update compulsion status on existing link to match Excel
                        $this->programme->courses()->updateExistingPivot($course->id, [
                            'is_compulsory' => $isCompulsory,
                        ]);
                        $this->stats['skipped']++;
                    }
                });

            } catch (\Exception $e) {
                Log::error("Error importing course row {$rowNum}: " . $e->getMessage());
                $this->stats['errors'][] = "Row {$rowNum}: " . $e->getMessage();
                $this->stats['skipped']++;
            }
        }
    }

    public function getStats()
    {
        return $this->stats;
    }
}
