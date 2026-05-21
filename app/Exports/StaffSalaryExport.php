<?php

namespace App\Exports;

use App\Models\Staff;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffSalaryExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Staff::with('user', 'department')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Staff Name',
            'Email',
            'Department',
            'Basic Salary',
            'Allowances',
            'Deductions',
            'Bonuses',
            'Bank Name',
            'Account Number',
            'Account Name',
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->user?->name,
            $row->user?->email,
            $row->department?->name,
            $row->basic_salary,
            $row->allowances,
            $row->deductions,
            $row->bonuses,
            $row->bank_name,
            $row->account_number,
            $row->account_name,
        ];
    }
}
