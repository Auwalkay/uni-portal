<?php

namespace App\Imports;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Staff;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PayrollImport implements ToModel, WithChunkReading, WithHeadingRow
{
    protected Payroll $payroll;

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
    }

    public function model(array $row)
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return null;
        }

        $item = null;

        // 1. Match by payroll_item_id
        if (!empty($row['payroll_item_id'])) {
            $item = PayrollItem::where('id', trim((string) $row['payroll_item_id']))
                ->where('payroll_id', $this->payroll->id)
                ->first();
        }

        // 2. Match by staff_id (staff_number)
        if (!$item && !empty($row['staff_id'])) {
            $staffNumber = trim((string) $row['staff_id']);
            $staff = Staff::where('staff_number', $staffNumber)->first();
            if ($staff) {
                $item = PayrollItem::where('staff_id', $staff->id)
                    ->where('payroll_id', $this->payroll->id)
                    ->first();
            }
        }

        // 3. Match by email
        if (!$item && !empty($row['email'])) {
            $email = trim((string) $row['email']);
            $item = PayrollItem::where('payroll_id', $this->payroll->id)
                ->whereHas('staff.user', function ($q) use ($email) {
                    $q->where('email', $email);
                })
                ->first();
        }

        if ($item) {
            $updated = false;

            if (array_key_exists('basic_salary', $row) && is_numeric($row['basic_salary'])) {
                $item->basic_salary = max(0, (float) $row['basic_salary']);
                $updated = true;
            }

            if (array_key_exists('allowances', $row) && is_numeric($row['allowances'])) {
                $item->total_allowances = max(0, (float) $row['allowances']);
                $updated = true;
            }

            if (array_key_exists('deductions', $row) && is_numeric($row['deductions'])) {
                $item->total_deductions = max(0, (float) $row['deductions']);
                $updated = true;
            }

            if (!empty($row['status'])) {
                $statusVal = strtolower(trim((string) $row['status']));
                if (in_array($statusVal, ['pending', 'excluded', 'paid'])) {
                    $item->status = $statusVal;
                    $updated = true;
                }
            }

            if (array_key_exists('remarks', $row)) {
                $item->remarks = $row['remarks'] !== null ? trim((string) $row['remarks']) : null;
                $updated = true;
            }

            if ($updated) {
                $item->recalculate();
                $item->save();
            }
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
