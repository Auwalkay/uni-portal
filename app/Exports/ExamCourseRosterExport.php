<?php

namespace App\Exports;

use App\Models\CourseRegistration;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExamCourseRosterExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected string $courseId;
    protected ?string $sessionId;
    protected ?string $semesterId;
    protected int $rowNumber = 0;

    public function __construct(string $courseId, ?string $sessionId = null, ?string $semesterId = null)
    {
        $this->courseId = $courseId;
        $this->sessionId = $sessionId;
        $this->semesterId = $semesterId;
    }

    public function query()
    {
        return CourseRegistration::query()
            ->where('course_id', $this->courseId)
            ->with([
                'student.user',
                'student.academicDepartment',
            ])
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->when($this->semesterId, fn ($q) => $q->where('semester_id', $this->semesterId))
            ->join('students', 'course_registrations.student_id', '=', 'students.id')
            ->orderBy('students.matriculation_number', 'asc')
            ->select('course_registrations.*');
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Matric Number',
            'Candidate Name',
            'Department',
            'Level',
            'Signature',
        ];
    }

    public function map($reg): array
    {
        $this->rowNumber++;

        $student = $reg->student;
        $user = $student?->user;
        $dept = $student?->academicDepartment ?? $student?->department;

        return [
            $this->rowNumber,
            $student?->matriculation_number ?? $student?->matric_number ?? 'N/A',
            $user?->name ?? 'N/A',
            $dept?->name ?? 'N/A',
            $student?->current_level ? "{$student->current_level} Lvl" : 'N/A',
            '', // Blank space for candidate signature in exam hall
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ],
        ];
    }
}
