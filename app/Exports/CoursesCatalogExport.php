<?php

namespace App\Exports;

use App\Models\Course;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CoursesCatalogExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Request $request;
    protected int $rowNum = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $user = $this->request->user();
        $search = $this->request->input('search');
        $facultyId = $this->request->input('faculty_id');
        $departmentId = $this->request->input('department_id');
        $level = $this->request->input('level');
        $semester = $this->request->input('semester');

        return Course::with('department.faculty', 'programme')
            ->when(!$user->can('manage_courses') && !$user->can('view_courses') && !$user->can('manage_academic_sessions'), function ($q) use ($user) {
                $q->whereHas('allocations', function ($aq) use ($user) {
                    $aq->whereHas('staff', fn($sq) => $sq->where('user_id', $user->id));
                });
            })
            ->when($search, fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($facultyId, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $facultyId)))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->when($semester, fn ($q) => $q->where('semester', (string) $semester))
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
            'Department',
            'Faculty',
            'Programme',
            'Status',
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
            $course->department?->name ?? 'N/A',
            $course->department?->faculty?->name ?? 'N/A',
            $course->programme?->name ?? 'All Programmes',
            $course->is_active ? 'Active' : 'Inactive',
        ];
    }
}
