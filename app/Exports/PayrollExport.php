<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Payroll $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function collection()
    {
        return $this->payroll->items()->with(['staff.user', 'staff.department'])->get();
    }

    public function headings(): array
    {
        return [
            'payroll_item_id',
            'staff_id',
            'staff_name',
            'email',
            'department',
            'basic_salary',
            'allowances',
            'deductions',
            'net_salary',
            'status',
            'remarks',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->staff?->staff_number,
            $row->staff?->user?->name,
            $row->staff?->user?->email,
            $row->staff?->department?->name,
            (float) $row->basic_salary,
            (float) $row->total_allowances,
            (float) $row->total_deductions,
            (float) $row->net_salary,
            $row->status,
            $row->remarks,
        ];
    }

    public function title(): string
    {
        return 'Payroll_' . $this->payroll->month . '_' . $this->payroll->year;
    }
}
