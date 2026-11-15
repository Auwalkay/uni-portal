<?php

namespace App\Imports;

use App\Enums\AttendanceStatus;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CourseAttendanceImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    protected Course $course;
    protected Session $session;
    protected string $attendanceDate;
    protected ?string $recordedBy;
    protected ?string $lectureSessionId;

    protected array $stats = [
        'processed' => 0,
        'present' => 0,
        'absent' => 0,
        'unregistered' => 0,
        'errors' => [],
    ];

    public function __construct(Course $course, Session $session, string $attendanceDate, ?string $recordedBy = null, ?string $lectureSessionId = null)
    {
        $this->course = $course;
        $this->session = $session;
        $this->attendanceDate = $attendanceDate;
        $this->recordedBy = $recordedBy;
        $this->lectureSessionId = $lectureSessionId;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        // 1. Gather all matric numbers in this chunk
        $matricList = [];
        foreach ($rows as $index => $row) {
            $matricRaw = $this->extractMatric($row);
            if (!empty($matricRaw)) {
                $matricList[] = strtoupper(trim((string) $matricRaw));
            }
        }

        if (empty($matricList)) {
            return;
        }

        // 2. Fetch all matching course registrations in bulk using indexed query
        $registrations = CourseRegistration::where('course_id', $this->course->id)
            ->where('session_id', $this->session->id)
            ->whereHas('student', fn ($q) => $q->whereIn('matriculation_number', $matricList))
            ->with('student:id,matriculation_number')
            ->get()
            ->keyBy(fn ($r) => strtoupper(trim((string) $r->student?->matriculation_number)));

        // 3. Build chunk batch for upsert
        $batch = [];
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $matricRaw = $this->extractMatric($row);

            if (empty($matricRaw)) {
                continue;
            }

            $matricKey = strtoupper(trim((string) $matricRaw));
            $reg = $registrations->get($matricKey);

            if (!$reg) {
                $this->stats['unregistered']++;
                if (count($this->stats['errors']) < 20) {
                    $this->stats['errors'][] = [
                        'row' => $rowNum,
                        'matric' => $matricKey,
                        'reason' => 'Student is not registered for this course in the current session.',
                    ];
                }
                continue;
            }

            $statusRaw = $row['status'] ?? $row['attendance'] ?? $row['present'] ?? null;
            $status = AttendanceStatus::fromInput($statusRaw !== null ? (string) $statusRaw : null);

            $batch[] = [
                'id' => (string) Str::uuid(),
                'course_lecture_session_id' => $this->lectureSessionId,
                'course_registration_id' => $reg->id,
                'student_id' => $reg->student_id,
                'course_id' => $this->course->id,
                'session_id' => $this->session->id,
                'attendance_date' => $this->attendanceDate,
                'status' => $status->value,
                'recorded_by' => $this->recordedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->stats['processed']++;
            if ($status === AttendanceStatus::PRESENT) {
                $this->stats['present']++;
            } else {
                $this->stats['absent']++;
            }
        }

        // 4. Ultra-fast bulk upsert using composite unique constraint
        if (!empty($batch)) {
            DB::table('student_course_attendances')->upsert(
                $batch,
                ['course_registration_id', 'attendance_date'],
                ['status', 'recorded_by', 'course_lecture_session_id', 'updated_at']
            );
        }
    }

    protected function extractMatric($row): ?string
    {
        return $row['matriculation_number']
            ?? $row['matric_number']
            ?? $row['matric_no']
            ?? $row['matric']
            ?? $row['student_id']
            ?? null;
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
