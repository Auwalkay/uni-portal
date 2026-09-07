<?php

namespace App\Exports;

use App\Models\Programme;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProgrammeCurriculumExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Programme $programme;
    protected int $rowNum = 0;

    public function __construct(Programme $programme)
    {
        $this->programme = $programme;
    }

    public function collection()
    {
        return $this->programme->courses()
            ->with('department')
            ->orderBy('level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get();
    }

    public function headings(): array
    {
        return [
            'S/N',
            'Course Code',
            'Course Title',
            'Credit Units',
            'Level',
            'Semester',
            'Requirement',
            'Department',
        ];
    }

    public function map($course): array
    {
        $this->rowNum++;
        $levelNum = (int)$course->level;
        $levelStr = $levelNum < 10 ? ($levelNum * 100) . ' Level' : $levelNum . ' Level';

        return [
            $this->rowNum,
            $course->code,
            $course->title,
            $course->units,
            $levelStr,
            'Semester ' . $course->semester,
            !empty($course->pivot?->is_compulsory) ? 'Compulsory' : 'Elective',
            $course->department?->name ?? 'N/A',
        ];
    }
}
