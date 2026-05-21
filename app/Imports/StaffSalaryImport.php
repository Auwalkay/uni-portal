<?php

namespace App\Imports;

use App\Models\Staff;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StaffSalaryImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Find staff by ID or Email
        $staff = null;
        if (isset($row['id'])) {
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

        return null; // Return null because we are updating existing records, not creating new ones
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|exists:staff,id',
            'email' => 'nullable|email',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
        ];
    }
}
