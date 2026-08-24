<?php

namespace App\Helpers;

use App\Models\Staff;

class StaffNumberHelper
{
    /**
     * Generate a unique staff number.
     * Format: MIUERP{YEAR}{RANDOM_4_DIGIT}
     *
     * @return string
     */
    public static function generate(): string
    {
        $year = date('Y');

        do {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $staffNumber = "MIUERP{$year}{$random}";
        } while (Staff::where('staff_number', $staffNumber)->exists());

        return $staffNumber;
    }
}
