<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Staff;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AttendanceImport implements ToModel, WithHeadingRow
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $staffId = $row['staff_id'];
        
        // Find staff by their internal reference or ID if it's a UUID
        $staff = Staff::where('id', $staffId)
            ->orWhere('employee_id', $staffId) // Assuming there's an employee_id column
            ->first();

        if (!$staff) {
            return null;
        }

        // Use updateOrCreate to prevent duplicates for the same day
        return Attendance::updateOrCreate(
            [
                'staff_id' => $staff->id,
                'date' => $this->date,
            ],
            [
                'clock_in' => $row['clock_in'] ?? null,
                'clock_out' => $row['clock_out'] ?? null,
                'status' => $row['clock_in'] ? 'present' : 'absent',
                'source' => 'excel',
            ]
        );
    }
}
