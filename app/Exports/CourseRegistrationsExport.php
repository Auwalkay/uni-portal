<?php

namespace App\Exports;

use App\Models\CourseRegistration;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseRegistrationsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $courseId;
    protected $sessionId;
    protected $level;
    protected $departmentId;
    protected $facultyId;
    protected $programmeId;

    public function __construct($courseId, $sessionId = null, $level = null, $departmentId = null, $facultyId = null, $programmeId = null)
    {
        $this->courseId = $courseId;
        $this->sessionId = $sessionId;
        $this->level = $level;
        $this->departmentId = $departmentId;
        $this->facultyId = $facultyId;
        $this->programmeId = $programmeId;
    }

    public function query()
    {
        return CourseRegistration::query()
            ->where('course_id', $this->courseId)
            ->with(['student.user', 'student.department', 'student.program', 'session'])
            ->when($this->sessionId, fn($q) => $q->where('session_id', $this->sessionId))
            ->when($this->level, fn($q) => $q->whereHas('student', fn($s) => $s->where('current_level', $this->level)))
            ->when($this->departmentId, fn($q) => $q->whereHas('student', fn($s) => $s->where('department_id', $this->departmentId)))
            ->when($this->facultyId, fn($q) => $q->whereHas('student.department', fn($d) => $d->where('faculty_id', $this->facultyId)))
            ->when($this->programmeId, fn($q) => $q->whereHas('student', fn($s) => $s->where('programme_id', $this->programmeId)))
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Matric Number',
            'Full Name',
            'Department',
            'Programme',
            'Level',
            'Session'
        ];
    }

    public function map($registration): array
    {
        return [
            $registration->student->matric_number,
            $registration->student->user->name ?? 'N/A',
            $registration->student->department->name ?? 'N/A',
            $registration->student->program->name ?? 'N/A',
            $registration->student->current_level ?? 'N/A', // Using current level
            $registration->session->name ?? 'N/A',
        ];
    }
}
