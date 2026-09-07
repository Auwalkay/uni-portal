<?php

namespace App\Exports;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacultiesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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

        return Faculty::withCount('departments')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Faculty Code',
            'Faculty Name',
            'Total Departments',
            'Status',
        ];
    }

    public function map($faculty): array
    {
        $this->rowNum++;

        return [
            $this->rowNum,
            $faculty->code,
            $faculty->name,
            $faculty->departments_count ?? 0,
            $faculty->is_active ? 'Active' : 'Inactive',
        ];
    }
}
