<?php

namespace App\Helpers;

use App\Models\Applicant;

class ApplicationNumberHelper
{
    /**
     * Generate a unique application number.
     * Format: APP/{YEAR}/{RANDOM_4_DIGIT}
     *
     * @return string
     */
    public static function generate(): string
    {
        $year = date('Y');

        do {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $appNumber = "APP/{$year}/{$random}";
        } while (Applicant::where('application_number', $appNumber)->exists());

        return $appNumber;
    }
}
