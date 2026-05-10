<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentFeesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Matric Number',
            'Faculty',
            'Department',
            'Program',
            'Level',
            'Billed Amount',
            'Paid Amount',
            'Balance',
            'Status',
            'Scholarship',
            'Discount (%)',
            'Last Payment Date'
        ];
    }

    public function map($student): array
    {
        return [
            $student->user->name,
            $student->matriculation_number,
            $student->faculty?->name,
            $student->department?->name,
            $student->program?->name,
            $student->current_level,
            $student->total_billed,
            $student->total_paid,
            $student->balance,
            strtoupper($student->fee_status),
            $student->scholarship?->name ?? 'None',
            $student->scholarship ? $student->scholarship->percentage . '%' : '0%',
            $student->last_payment_date ? \Carbon\Carbon::parse($student->last_payment_date)->format('Y-m-d H:i:s') : 'N/A'
        ];
    }
}
