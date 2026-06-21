<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Session;
use App\Models\CourseRegistration;
use App\Services\GradingService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ResultImport implements ToCollection, WithHeadingRow
{
    protected $course;
    protected $session;
    protected $gradingService;

    public function __construct(Course $course, Session $session, GradingService $gradingService)
    {
        $this->course = $course;
        $this->session = $session;
        $this->gradingService = $gradingService;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if (!isset($row['matric_number']))
                continue;

            $rowNum = $index + 2;
            $matric = trim($row['matric_number']);
            $ca = isset($row['ca']) && $row['ca'] !== '' ? floatval($row['ca']) : 0;
            $exam = isset($row['exam']) && $row['exam'] !== '' ? floatval($row['exam']) : 0;

            if ($ca < 0 || $ca > 40) {
                throw new \Exception("Row {$rowNum}: The CA score for student with matric number '{$matric}' must be between 0 and 40 (currently {$row['ca']}).");
            }

            if ($exam < 0 || $exam > 80) {
                throw new \Exception("Row {$rowNum}: The Exam score for student with matric number '{$matric}' must be between 0 and 80 (currently {$row['exam']}).");
            }

            if ($ca + $exam > 100) {
                throw new \Exception("Row {$rowNum}: The total score (CA + Exam) for student with matric number '{$matric}' must not exceed 100 (currently " . ($ca + $exam) . ").");
            }

            // Find Student Registration
            // We join students to check matric number
            $registration = CourseRegistration::where('course_id', $this->course->id)
                ->where('session_id', $this->session->id)
                ->whereHas('student', function ($q) use ($matric) {
                    $q->where('matric_number', $matric);
                })
                ->first();

            if ($registration) {
                $total = $ca + $exam;
                $grading = $this->gradingService->calculate($total);

                $registration->update([
                    'ca_score' => $ca,
                    'exam_score' => $exam,
                    'score' => $total,
                    'grade' => $grading['grade'],
                    'grade_point' => $grading['point'],
                    'is_absent' => false,
                ]);
            }
        }
    }
}
