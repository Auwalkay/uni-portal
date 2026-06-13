<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GlobalCourseTemplateExport implements FromCollection, WithHeadings
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
                'department_code' => 'CSC',
                'programme_name' => 'B.Sc Computer Science',
            ],
            [
                'course_code' => 'MTH 102',
                'course_title' => 'General Mathematics II',
                'units' => '3',
                'level' => '100',
                'semester' => '2',
                'department_code' => 'MTH',
                'programme_name' => 'B.Sc Mathematics',
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
            'department_code',
            'programme_name'
        ];
    }
}
