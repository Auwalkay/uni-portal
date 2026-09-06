<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\ExamInvigilator;
use App\Models\ExamSchedule;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ExamScheduleImport
{
    protected $sessionId;
    protected $semesterId;
    protected $stats = [
        'created' => 0,
        'updated' => 0,
        'invigilators_assigned' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function __construct($sessionId, $semesterId)
    {
        $this->sessionId = $sessionId;
        $this->semesterId = $semesterId;
    }

    public function process($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Could not open file.");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            throw new \Exception("Empty CSV file.");
        }

        // Normalize header row
        $cleanHeader = array_map(function ($col) {
            return Str::slug(trim(strtolower($col)), '_');
        }, $header);

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue; // Skip empty lines
            }

            $data = array_combine($cleanHeader, array_pad($row, count($cleanHeader), null));

            $courseCode = trim($data['course_code'] ?? '');
            if (!$courseCode) {
                $this->stats['skipped']++;
                $this->stats['errors'][] = "Row {$rowNum}: Missing course_code.";
                continue;
            }

            // Find Course
            $course = Course::where('code', $courseCode)
                ->orWhere('code', str_replace(' ', '', $courseCode))
                ->orWhere('code', preg_replace('/([A-Z]+)(\d+)/', '$1 $2', $courseCode))
                ->first();

            if (!$course) {
                $this->stats['skipped']++;
                $this->stats['errors'][] = "Row {$rowNum}: Course '{$courseCode}' not found.";
                continue;
            }

            $examDate = trim($data['exam_date'] ?? '');
            $startTime = trim($data['start_time'] ?? '09:00');
            $endTime = trim($data['end_time'] ?? '12:00');
            $venue = trim($data['venue'] ?? 'Main Hall');
            $capacity = intval($data['max_capacity'] ?? 100);
            $examType = strtolower(trim($data['exam_type'] ?? 'final'));
            if (!in_array($examType, ['final', 'mid_term', 'cbt', 'resit'])) {
                $examType = 'final';
            }

            try {
                $parsedDate = Carbon::parse($examDate)->format('Y-m-d');
            } catch (\Exception $e) {
                $this->stats['skipped']++;
                $this->stats['errors'][] = "Row {$rowNum}: Invalid exam_date format '{$examDate}'. Use YYYY-MM-DD.";
                continue;
            }

            // Create or update Exam Schedule
            $schedule = ExamSchedule::updateOrCreate(
                [
                    'session_id' => $this->sessionId,
                    'semester_id' => $this->semesterId,
                    'course_id' => $course->id,
                    'exam_date' => $parsedDate,
                ],
                [
                    'reference_id' => 'EXM-' . strtoupper(substr(uniqid(), -6)),
                    'department_id' => $course->department_id,
                    'level' => $course->level,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'venue' => $venue,
                    'exam_type' => $examType,
                    'max_capacity' => $capacity > 0 ? $capacity : 100,
                    'created_by' => auth()->id(),
                ]
            );

            if ($schedule->wasRecentlyCreated) {
                $this->stats['created']++;
            } else {
                $this->stats['updated']++;
            }

            // Assign Invigilators (Chief + Assistant 1 + Assistant 2)
            $invigilatorKeys = [
                'chief' => ['chief_invigilator', 'chief_invigilator_staff_number', 'chief'],
                'assistant_1' => ['assistant_invigilator_1', 'assistant_invigilator_1_staff_number', 'assistant_1'],
                'assistant_2' => ['assistant_invigilator_2', 'assistant_invigilator_2_staff_number', 'assistant_2'],
            ];

            foreach ($invigilatorKeys as $roleKey => $possibleCols) {
                $staffIdentifier = null;
                foreach ($possibleCols as $colName) {
                    if (!empty($data[$colName])) {
                        $staffIdentifier = trim($data[$colName]);
                        break;
                    }
                }

                if ($staffIdentifier) {
                    $staff = Staff::where('staff_number', $staffIdentifier)
                        ->orWhereHas('user', fn ($uq) => $uq->where('email', $staffIdentifier)->orWhere('name', 'like', "%{$staffIdentifier}%"))
                        ->first();

                    if ($staff) {
                        ExamInvigilator::updateOrCreate(
                            [
                                'exam_schedule_id' => $schedule->id,
                                'staff_id' => $staff->id,
                            ],
                            [
                                'role' => str_starts_with($roleKey, 'chief') ? 'chief' : 'assistant',
                                'status' => 'assigned',
                            ]
                        );
                        $this->stats['invigilators_assigned']++;
                    }
                }
            }
        }

        fclose($handle);

        return $this->stats;
    }

    public function getStats()
    {
        return $this->stats;
    }
}
