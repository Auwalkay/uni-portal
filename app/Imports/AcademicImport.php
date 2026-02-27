<?php

namespace App\Imports;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class AcademicImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $processedCount = 0;
    protected $faculties = [];
    protected $departments = [];

    public function model(array $row)
    {
        // 1. Find or Create Faculty
        $facultyName = trim($row['faculty_name']);
        $facultyCode = strtoupper(trim($row['faculty_code']));

        if (!isset($this->faculties[$facultyCode])) {
            $faculty = Faculty::firstOrCreate(
                ['code' => $facultyCode],
                ['name' => $facultyName, 'is_active' => true]
            );
            $this->faculties[$facultyCode] = $faculty->id;
        }
        $facultyId = $this->faculties[$facultyCode];

        // 2. Find or Create Department
        $deptName = trim($row['department_name']);
        $deptCode = strtoupper(trim($row['department_code']));
        $deptKey = $facultyCode . '_' . $deptCode;

        if (!isset($this->departments[$deptKey])) {
            $department = Department::firstOrCreate(
                ['code' => $deptCode, 'faculty_id' => $facultyId],
                ['name' => $deptName, 'is_active' => true]
            );
            $this->departments[$deptKey] = $department->id;
        }
        $departmentId = $this->departments[$deptKey];

        // 3. Create Programme
        $progName = trim($row['programme_name']);

        $programme = Programme::firstOrCreate(
            [
                'name' => $progName,
                'department_id' => $departmentId
            ],
            [
                'award' => $row['award'] ?? 'ND',
                'duration' => $row['duration'] ?? 2,
                'is_active' => true
            ]
        );

        $this->processedCount++;

        return $programme;
    }

    public function rules(): array
    {
        return [
            'faculty_name' => 'required|string|max:255',
            'faculty_code' => 'required|string|max:20',
            'department_name' => 'required|string|max:255',
            'department_code' => 'required|string|max:20',
            'programme_name' => 'required|string|max:255',
            'award' => 'nullable|string|max:50',
            'duration' => 'nullable|integer|min:1|max:10',
        ];
    }

    public function getProcessedCount()
    {
        return $this->processedCount;
    }
}
