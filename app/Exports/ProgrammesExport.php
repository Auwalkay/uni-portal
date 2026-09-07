<?php

namespace App\Exports;

use App\Models\Programme;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProgrammesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Request $request;
    protected int $rowNum = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $search = $this->request->input('search');
        $facultyId = $this->request->input('faculty_id');
        $departmentId = $this->request->input('department_id');
        $programType = $this->request->input('program_type');

        return Programme::with('department.faculty')
            ->withCount('courses')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($facultyId, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $facultyId)))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($programType, fn ($q) => $q->where('type', $programType))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Programme Name',
            'Degree Type',
            'Department',
            'Faculty',
            'Total Courses',
            'Scholarship Eligible',
            'Status',
        ];
    }

    public function map($programme): array
    {
        $this->rowNum++;

        return [
            $this->rowNum,
            $programme->name,
            $programme->type,
            $programme->department?->name ?? 'N/A',
            $programme->department?->faculty?->name ?? 'N/A',
            $programme->courses_count ?? 0,
            $programme->scholarship_eligible ? 'Yes' : 'No',
            $programme->is_active ? 'Active' : 'Inactive',
        ];
    }
}
