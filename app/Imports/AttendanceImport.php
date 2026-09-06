<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Staff;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AttendanceImport implements ToModel, WithChunkReading, WithHeadingRow
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
        $staffId = trim((string)($row['staff_id'] ?? ''));
        
        if (empty($staffId)) {
            return null;
        }
        
        // Find staff by staff_number or UUID id
        $staff = Staff::where('staff_number', $staffId)
            ->orWhere('id', $staffId)
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

    public function chunkSize(): int
    {
        return 500;
    }
}
