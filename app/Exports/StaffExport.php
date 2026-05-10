<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = User::role('staff')
            ->with(['staff.department.faculty', 'roles']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($sq) use ($search) {
                        $sq->where('staff_number', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($this->filters['role_id'])) {
            $query->whereHas('roles', function ($q) {
                $q->where('id', $this->filters['role_id']);
            });
        }

        if (!empty($this->filters['faculty_id'])) {
            $query->whereHas('staff.department', function ($q) {
                if ($this->filters['faculty_id'] === 'NON_ACADEMIC') {
                    $q->whereNull('faculty_id');
                } else {
                    $q->where('faculty_id', $this->filters['faculty_id']);
                }
            });
        }

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('staff', function ($q) {
                $q->where('department_id', $this->filters['department_id']);
            });
        }

        return $query->orderBy('name', 'asc');
    }

    public function headings(): array
    {
        return [
            'Staff Number',
            'Full Name',
            'Email',
            'Phone',
            'Gender',
            'Designation',
            'Department',
            'Faculty',
            'Role',
            'Type',
            'Highest Qualification',
            'Date Joined'
        ];
    }

    public function map($user): array
    {
        $staff = $user->staff;
        $role = $user->roles->whereNotIn('name', ['staff', 'admin', 'student', 'applicant'])->first();

        return [
            $staff?->staff_number ?? 'N/A',
            $user->name,
            $user->email,
            $staff?->phone_number ?? 'N/A',
            ucfirst($staff?->gender ?? 'N/A'),
            $staff?->designation ?? 'N/A',
            $staff?->department?->name ?? 'N/A',
            $staff?->department?->faculty?->name ?? 'Non-Academic',
            $role?->name ?? 'Staff',
            $staff?->is_academic ? 'Academic' : 'Non-Academic',
            $staff?->highest_qualification ?? 'N/A',
            $staff?->date_joined ? \Carbon\Carbon::parse($staff->date_joined)->format('Y-m-d') : 'N/A'
        ];
    }
}
