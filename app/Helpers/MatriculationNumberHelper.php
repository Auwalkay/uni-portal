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

            // Extract the starting digit based on entry level
            $level = isset($data['level']) ? (string)$data['level'] : '100';
            $startDigit = substr(trim($level), 0, 1);
            if (!in_array($startDigit, ['1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                $startDigit = '1';
            }

            // If format contains {SEQUENCE}, calculate it
            $sequence = '';
            $maxCount = 0;
            if (str_contains($format, '{SEQUENCE}')) {
                // Calculate prefix for counting: replace all placeholders except SEQUENCE
                $prefix = str_replace(
                    ['{YEAR}', '{RANDOM}', '{DEPT}', '{FACULTY}'],
                    [$year, '', $deptCode, $facCode],
                    $format
                );
                $prefix = str_replace('{SEQUENCE}', '', $prefix);
                
                // Retrieve existing matriculation numbers starting with the prefix
                $existingMatrics = Student::where('matriculation_number', 'LIKE', $prefix . '%')
                    ->lockForUpdate()
                    ->pluck('matriculation_number');

                foreach ($existingMatrics as $matric) {
                    $seqPart = substr($matric, strlen($prefix));
                    if (strlen($seqPart) >= 3) {
                        $counterStr = substr($seqPart, -3);
                        if (is_numeric($counterStr)) {
                            $counterVal = (int) $counterStr;
                            if ($counterVal > $maxCount) {
                                $maxCount = $counterVal;
                            }
                        }
                    }
                }
                
                // Pad the sequence counter to 3 digits (e.g., 001 or continuing sequence)
                $seqCounter = str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                
                // Combine them to form a 4-digit sequence (e.g., 2001 or 2046)
                $sequence = $startDigit . $seqCounter;
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
                        $maxCount++;
                        $seqCounter = str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                        $sequence = $startDigit . $seqCounter;
                        $matricNumber = str_replace(
                            ['{YEAR}', '{SEQUENCE}', '{DEPT}', '{FACULTY}'],
                            [$year, $sequence, $deptCode, $facCode],
                            $format
                        );
                    }
                    break; 
                }

            } while (Student::where('matriculation_number', $matricNumber)->exists());

            return strtoupper($matricNumber);
        });
    }
}
