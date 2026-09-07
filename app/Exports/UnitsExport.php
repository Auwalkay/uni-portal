<?php

namespace App\Exports;

use App\Models\Unit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UnitsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        $departmentId = $this->request->input('department_id');

        return Unit::with('department')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Unit Code',
            'Unit Name',
            'Parent Department',
            'Status',
        ];
    }

    public function map($unit): array
    {
        $this->rowNum++;

        return [
            $this->rowNum,
            $unit->code,
            $unit->name,
            $unit->department?->name ?? 'N/A',
            $unit->is_active ? 'Active' : 'Inactive',
        ];
    }
}
