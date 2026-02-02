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
        foreach ($rows as $row) {
            if (!isset($row['matric_number']))
                continue;

            $matric = trim($row['matric_number']);
            $ca = $row['ca'] ?? 0;
            $exam = $row['exam'] ?? 0;

            // Find Student Registration
            // We join students to check matric number
            $registration = CourseRegistration::where('course_id', $this->course->id)
                ->where('session_id', $this->session->id)
                ->whereHas('student', function ($q) use ($matric) {
                    $q->where('matric_number', $matric);
                })
                ->first();

            if ($registration) {
                // Update
                $total = $ca + $exam;
                if ($total > 100)
                    $total = 100;

                $grading = $this->gradingService->calculate($total);

                $registration->update([
                    'ca_score' => $ca,
                    'exam_score' => $exam,
                    'score' => $total,
                    'grade' => $grading['grade'],
                    'grade_point' => $grading['point'],
                ]);
            }
        }
    }
}
