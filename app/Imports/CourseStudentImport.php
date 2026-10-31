<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Services\AcademicCacheService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CourseStudentImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    protected Course $course;
    protected Session $session;
    protected ?Semester $semester;
    protected bool $skipExisting;

    protected array $stats = [
        'enrolled' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    public function __construct(Course $course, Session $session, ?Semester $semester = null, bool $skipExisting = true)
    {
        $this->course = $course;
        $this->session = $session;
        $this->semester = $semester;
        $this->skipExisting = $skipExisting;

        if (! $this->semester) {
            $semesters = Semester::where('session_id', $session->id)->get();
            $courseSem = strval($course->semester);
            $this->semester = $semesters->first(function ($s) use ($courseSem) {
                if ($courseSem === '1') {
                    return stripos($s->name, 'First') !== false || $s->name === '1';
                }
                return stripos($s->name, 'Second') !== false || $s->name === '2';
            }) ?? $semesters->first();
        }
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 for 0-index, +1 for header row

            // Extract matriculation number from multiple possible header variations
            $matricRaw = $row['matriculation_number'] ?? $row['matric_number'] ?? $row['matric_no'] ?? $row['matric'] ?? null;

            if (empty($matricRaw)) {
                continue;
            }

            $matric = trim(strval($matricRaw));

            // Find student
            $student = Student::where('matriculation_number', $matric)->first();

            if (! $student) {
                $this->stats['errors'][] = [
                    'row' => $rowNum,
                    'matric' => $matric,
                    'reason' => "Student with matriculation number '{$matric}' was not found in the student database.",
                ];
                $this->stats['skipped']++;
                continue;
            }

            // Check if already registered for this course in this session
            $existingReg = CourseRegistration::where('student_id', $student->id)
                ->where('course_id', $this->course->id)
                ->where('session_id', $this->session->id)
                ->first();

            if ($existingReg) {
                if ($this->skipExisting) {
                    $this->stats['skipped']++;
                    continue;
                }
            }

            // Ensure student session exists
            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $this->session->id],
                ['level' => $student->current_level ?? 100, 'status' => 'active']
            );

            if (! $existingReg) {
                CourseRegistration::create([
                    'student_id' => $student->id,
                    'course_id' => $this->course->id,
                    'session_id' => $this->session->id,
                    'semester_id' => $this->semester?->id,
                    'student_session_id' => $studentSession->id,
                ]);

                $this->stats['enrolled']++;
            }
        }

        AcademicCacheService::clearTimetableCache();
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getEnrolledCount(): int
    {
        return $this->stats['enrolled'];
    }

    public function getSkippedCount(): int
    {
        return $this->stats['skipped'];
    }

    public function getErrors(): array
    {
        return $this->stats['errors'];
    }
}
