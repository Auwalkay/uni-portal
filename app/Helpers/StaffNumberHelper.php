<?php

namespace App\Helpers;

use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class StaffNumberHelper
{
    /**
     * Generate a unique sequential staff number in ascending order.
     * Format: MIUERP{YEAR}{4_DIGIT_SEQUENCE} (e.g. MIUERP20260001, MIUERP20260002)
     *
     * @param int|null $year
     * @return string
     */
    public static function generate(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $prefix = "MIUERP{$year}";

        return DB::transaction(function () use ($prefix) {
            // Retrieve all existing staff numbers starting with this prefix, locked for update
            $existingNumbers = Staff::where('staff_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->pluck('staff_number')
                ->map(function ($num) use ($prefix) {
                    $suffix = substr($num, strlen($prefix));
                    return is_numeric($suffix) ? (int) $suffix : null;
                })
                ->filter()
                ->flip(); // Fast O(1) hash table lookup

            // Find lowest available sequence starting from 1 (0001)
            $sequence = 1;
            while (isset($existingNumbers[$sequence])) {
                $sequence++;
            }

            // Pad to at least 4 digits (e.g. 0001, 0002)
            $paddedSequence = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            return "{$prefix}{$paddedSequence}";
        });
    }
}
