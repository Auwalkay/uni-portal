<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProgrammeCourseTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'course_code' => 'CSC 101',
                'course_title' => 'Introduction to Computer Science',
                'units' => '3',
                'level' => '100',
                'semester' => '1',
                'is_compulsory' => '1',
            ],
            [
                'course_code' => 'MTH 102',
                'course_title' => 'General Mathematics II',
                'units' => '3',
                'level' => '100',
                'semester' => '2',
                'is_compulsory' => '0',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'course_code',
            'course_title',
            'units',
            'level',
            'semester',
            'is_compulsory'
        ];
    }
}
