<?php

namespace App\Exports;

use App\Models\Department;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DepartmentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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

        return Department::with('faculty')
            ->withCount(['programmes', 'units'])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($facultyId, function ($q) use ($facultyId) {
                if ($facultyId === 'NON_ACADEMIC') {
                    $q->whereNull('faculty_id');
                } else {
                    $q->where('faculty_id', $facultyId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Department Code',
            'Department Name',
            'Faculty',
            'Type',
            'Total Programmes',
            'Total Units',
            'Status',
        ];
    }

    public function map($department): array
    {
        $this->rowNum++;

        return [
            $this->rowNum,
            $department->code,
            $department->name,
            $department->faculty?->name ?? 'N/A (Central Admin)',
            $department->is_academic ? 'Academic' : 'Non-Academic',
            $department->programmes_count ?? 0,
            $department->units_count ?? 0,
            $department->is_active ? 'Active' : 'Inactive',
        ];
    }
}
