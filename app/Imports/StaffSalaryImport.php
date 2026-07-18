<?php

namespace App\Imports;

use App\Models\Staff;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StaffSalaryImport implements ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Find staff by Staff ID (staff_number), UUID id, or Email
        $staff = null;
        if (isset($row['staff_id'])) {
            $staff = Staff::where('staff_number', $row['staff_id'])->first();
        }

        if (!$staff && isset($row['id'])) {
            $staff = Staff::find($row['id']);
        }

        if (!$staff && isset($row['email'])) {
            $staff = Staff::whereHas('user', function ($q) use ($row) {
                $q->where('email', $row['email']);
            })->first();
        }

        if ($staff) {
            $staff->update([
                'basic_salary' => $row['basic_salary'] ?? $staff->basic_salary,
                'allowances' => $row['allowances'] ?? $staff->allowances,
                'deductions' => $row['deductions'] ?? $staff->deductions,
                'bonuses' => $row['bonuses'] ?? $staff->bonuses,
                'bank_name' => $row['bank_name'] ?? $staff->bank_name,
                'account_number' => $row['account_number'] ?? $staff->account_number,
                'account_name' => $row['account_name'] ?? $staff->account_name,
            ]);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'staff_id' => 'nullable|exists:staff,staff_number',
            'id' => 'nullable|exists:staff,id',
            'email' => 'nullable|email',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
