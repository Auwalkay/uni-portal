<?php

namespace App\Helpers;

use App\Models\Student;

class MatriculationNumberHelper
{
    /**
     * Generate a unique matriculation number.
     * Format: UNI/{YEAR}/{RANDOM_4_DIGIT}
     *
     * @return string
     */
    public static function generate(): string
    {
        $year = date('Y');

        do {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $matricNumber = "UNI/{$year}/{$random}";
        } while (Student::where('matriculation_number', $matricNumber)->exists());

        return $matricNumber;
    }
}
