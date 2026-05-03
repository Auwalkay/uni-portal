<?php

namespace App\Helpers;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

class MatriculationNumberHelper
{
    /**
     * Generate a unique matriculation number.
     * Format: UNI/{YEAR}/{RANDOM_4_DIGIT}
     *
     * @return string
     */
    public static function generate(?array $data = null): string
    {
        return DB::transaction(function () use ($data) {
            $year = date('y');
            $format = \App\Models\SystemSetting::get('matric_format', 'MIU{YEAR}{SEQUENCE}');

            $deptCode = $data['dept_code'] ?? 'GEN';
            $facCode = $data['fac_code'] ?? 'GEN';

            // If format contains {SEQUENCE}, calculate it
            $sequence = '';
            $count = 0;
            if (str_contains($format, '{SEQUENCE}')) {
                // Calculate prefix for counting: replace all placeholders except SEQUENCE
                $prefix = str_replace(
                    ['{YEAR}', '{RANDOM}', '{DEPT}', '{FACULTY}'],
                    [$year, '', $deptCode, $facCode],
                    $format
                );
                $prefix = str_replace('{SEQUENCE}', '', $prefix);
                
                // Get the count of students with this pattern and lock them to prevent race conditions
                $count = Student::where('matriculation_number', 'LIKE', $prefix . '%')
                    ->lockForUpdate()
                    ->count();
                
                $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            }

            do {
                $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $matricNumber = str_replace(
                    ['{YEAR}', '{RANDOM}', '{SEQUENCE}', '{DEPT}', '{FACULTY}'],
                    [$year, $random, $sequence, $deptCode, $facCode],
                    $format
                );

                // If we are using sequence, we don't need to loop unless there's a collision
                if (!str_contains($format, '{RANDOM}')) {
                    // If sequence already exists, increment it
                    while (Student::where('matriculation_number', $matricNumber)->exists()) {
                        $count++;
                        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
                        $matricNumber = str_replace(
                            ['{YEAR}', '{SEQUENCE}', '{DEPT}', '{FACULTY}'],
                            [$year, $sequence, $deptCode, $facCode],
                            $format
                        );
                    }
                    break; 
                }

            } while (Student::where('matriculation_number', $matricNumber)->exists());

            return $matricNumber;
        });
    }
}
