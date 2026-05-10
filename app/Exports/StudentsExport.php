<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Student::query()
            ->with(['user', 'academicDepartment.faculty', 'admittedSession', 'program', 'scholarship']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($this->filters['session_id'])) {
            $query->where('admitted_session_id', $this->filters['session_id']);
        }

        if (!empty($this->filters['faculty_id'])) {
            $query->whereHas('academicDepartment', function ($q) {
                $q->where('faculty_id', $this->filters['faculty_id']);
            });
        }

        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }

        if (!empty($this->filters['level'])) {
            $query->where('current_level', $this->filters['level']);
        }

        if (!empty($this->filters['program_id'])) {
            $query->where('program_id', $this->filters['program_id']);
        }

        if (!empty($this->filters['scholarship_id'])) {
            if ($this->filters['scholarship_id'] === 'NONE' || $this->filters['scholarship_id'] === 'none') {
                $query->whereNull('scholarship_id');
            } else {
                $query->where('scholarship_id', $this->filters['scholarship_id']);
            }
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('matriculation_number', 'asc');
    }

    public function headings(): array
    {
        return [
            'Matric Number',
            'Full Name',
            'Email',
            'Phone',
            'Gender',
            'Level',
            'Faculty',
            'Department',
            'Programme',
            'Entry Mode',
            'Admission Session',
            'Scholarship',
            'JAMB Number'
        ];
    }

    public function map($student): array
    {
        return [
            $student->matriculation_number,
            $student->user->name,
            $student->user->email,
            $student->phone_number,
            ucfirst($student->gender),
            $student->current_level,
            $student->academicDepartment?->faculty?->name ?? 'N/A',
            $student->academicDepartment?->name ?? 'N/A',
            $student->program?->name ?? 'N/A',
            $student->entry_mode,
            $student->admittedSession?->name ?? 'N/A',
            $student->scholarship?->name ?? 'None',
            $student->jamb_registration_number ?? 'N/A'
        ];
    }
}
