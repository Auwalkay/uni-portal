<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GlobalCourseImport implements ToCollection, WithHeadingRow
{
    private $stats = [
        'created' => 0,
        'linked' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $rowNum = $index + 2;

                $codeRaw = $row['course_code'] ?? $row['code'] ?? null;
                $titleRaw = $row['course_title'] ?? $row['title'] ?? null;
                $unitsRaw = $row['units'] ?? $row['credit_units'] ?? null;
                $levelRaw = $row['level'] ?? null;
                $semesterRaw = $row['semester'] ?? null;
                $deptCodeRaw = $row['department_code'] ?? $row['department'] ?? null;
                $progNameRaw = $row['programme_name'] ?? $row['programme'] ?? null;

                // Skip completely empty rows
                if (empty($codeRaw) && empty($titleRaw) && empty($deptCodeRaw)) {
                    continue;
                }

                $code = strtoupper(trim((string)$codeRaw));
                $title = trim((string)$titleRaw);
                $units = empty($unitsRaw) ? 3 : intval($unitsRaw);
                $level = empty($levelRaw) ? 100 : intval($levelRaw);
                $deptCode = strtoupper(trim((string)$deptCodeRaw));

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

                if (empty($deptCode)) {
                    $this->stats['errors'][] = "Row {$rowNum}: Department code is required for code '$code'.";
                    $this->stats['skipped']++;
                    continue;
                }

                // Find Department
                $dept = Department::where('code', $deptCode)->first();
                if (!$dept) {
                    $this->stats['errors'][] = "Row {$rowNum}: Department with code '$deptCode' not found.";
                    $this->stats['skipped']++;
                    continue;
                }

                // Map semester
                $semString = strtolower(trim((string)$semesterRaw));
                $semester = '1';
                if ($semString === '2' || str_contains($semString, 'second') || str_contains($semString, '2nd')) {
                    $semester = '2';
                }

                // Map is_compulsory (if linking to programme)
                $compulsoryRaw = $row['is_compulsory'] ?? $row['type'] ?? $row['compulsory'] ?? null;
                $compString = strtolower(trim((string)$compulsoryRaw));
                $isCompulsory = false;
                if (
                    $compString === '1' || 
                    $compString === 'true' || 
                    $compString === 'yes' || 
                    str_contains($compString, 'core') || 
                    str_contains($compString, 'compulsory') ||
                    empty($compulsoryRaw)
                ) {
                    $isCompulsory = true;
                }

                DB::transaction(function () use ($code, $title, $units, $level, $semester, $dept, $progNameRaw, $isCompulsory) {
                    // Find or create global course record
                    $course = Course::where('code', $code)->first();
                    $createdNew = false;
                    
                    if (!$course) {
                        $course = Course::create([
                            'id' => Str::uuid()->toString(),
                            'code' => $code,
                            'title' => $title,
                            'units' => $units,
                            'level' => $level,
                            'semester' => $semester,
                            'department_id' => $dept->id,
                            'is_active' => true,
                        ]);
                        $this->stats['created']++;
                        $createdNew = true;
                    }

                    // Optional Programme Linkage
                    if (!empty($progNameRaw)) {
                        $progName = trim((string)$progNameRaw);
                        $programme = Programme::where('name', $progName)->first();
                        
                        if ($programme) {
                            $exists = $programme->courses()->where('course_id', $course->id)->exists();
                            if (!$exists) {
                                $programme->courses()->attach($course->id, [
                                    'id' => Str::uuid()->toString(),
                                    'is_compulsory' => $isCompulsory,
                                ]);
                                $this->stats['linked']++;
                            } else {
                                $programme->courses()->updateExistingPivot($course->id, [
                                    'is_compulsory' => $isCompulsory,
                                ]);
                                if (!$createdNew) {
                                    $this->stats['skipped']++;
                                }
                            }
                        } else {
                            $this->stats['errors'][] = "Course '$code' created/found but programme '$progName' was not found.";
                            if (!$createdNew) {
                                $this->stats['skipped']++;
                            }
                        }
                    } else {
                        if (!$createdNew) {
                            $this->stats['skipped']++;
                        }
                    }
                });

            } catch (\Exception $e) {
                Log::error("Error importing global course row {$rowNum}: " . $e->getMessage());
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
