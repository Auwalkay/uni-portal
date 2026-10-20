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
        $user = auth()->user();
        $query = Student::query()
            ->with(['user', 'academicDepartment.faculty', 'admittedSession', 'program', 'scholarship']);

        if ($user) {
            if (!$user->can('manage_users') && !$user->hasAnyRole(['admin', 'super_admin', 'vc', 'ict_admin', 'registrar', 'bursar', 'admission_director'])) {
                $staff = $user->staff?->loadMissing('department');
                if ($user->hasRole('dean') || $user->can('view_faculty_students')) {
                    $facultyId = $staff?->department?->faculty_id;
                    if ($facultyId) {
                        $query->whereHas('academicDepartment', fn($q) => $q->where('faculty_id', $facultyId));
                    }
                } elseif ($user->hasRole('hod') || $user->can('view_department_students')) {
                    $departmentId = $staff?->department_id;
                    if ($departmentId) {
                        $query->where('department_id', $departmentId);
                    }
                } else {
                    $query->whereHas('registrations', function ($q) use ($user) {
                        $q->whereHas('course', function ($cq) use ($user) {
                            $cq->whereHas('allocations', function ($aq) use ($user) {
                                $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                            });
                        });
                    });
                }
            }
        }

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

        if (!empty($this->filters['gender']) && $this->filters['gender'] !== 'ALL_GENDERS' && $this->filters['gender'] !== 'all') {
            $query->where('gender', strtolower($this->filters['gender']));
        }

        if (!empty($this->filters['status']) && $this->filters['status'] !== 'ALL_STATUS' && $this->filters['status'] !== 'all') {
            $query->whereHas('user', function ($q) {
                $q->where('is_active', $this->filters['status'] === 'active');
            });
        }

        if (!empty($this->filters['entry_mode']) && $this->filters['entry_mode'] !== 'ALL_MODES' && $this->filters['entry_mode'] !== 'all') {
            $query->where('entry_mode', $this->filters['entry_mode']);
        }

        if (!empty($this->filters['age_range']) && $this->filters['age_range'] !== 'ALL_AGES' && $this->filters['age_range'] !== 'all') {
            $range = $this->filters['age_range'];
            if ($range === '15-19') {
                $query->whereNotNull('dob')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 15')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) <= 19');
            } elseif ($range === '20-30') {
                $query->whereNotNull('dob')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 20')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) <= 30');
            } elseif ($range === '30_above' || $range === '30+') {
                $query->whereNotNull('dob')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 30');
            }
        } else {
            if (!empty($this->filters['min_age'])) {
                $query->whereNotNull('dob')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= ?', [(int) $this->filters['min_age']]);
            }

            if (!empty($this->filters['max_age'])) {
                $query->whereNotNull('dob')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) <= ?', [(int) $this->filters['max_age']]);
            }

            if (!empty($this->filters['age']) && empty($this->filters['min_age']) && empty($this->filters['max_age'])) {
                $query->whereNotNull('dob')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) = ?', [(int) $this->filters['age']]);
            }
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
            'Date of Birth',
            'Age',
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
            ucfirst($student->gender ?? ''),
            $student->dob ?? 'N/A',
            $student->age ?? 'N/A',
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
