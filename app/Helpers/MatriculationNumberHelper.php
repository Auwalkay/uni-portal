<?php

namespace App\Helpers;

use App\Models\Programme;
use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

class MatriculationNumberHelper
{
    /**
     * Generate a unique matriculation number.
     *
     * @param array|null $data
     * @return string
     */
    public static function generate(?array $data = null): string
    {
        // If a programme instance or ID is provided, route through generateForProgram
        if (!empty($data['programme']) && $data['programme'] instanceof Programme) {
            return static::generateForProgram($data['programme'], $data);
        }

        if (!empty($data['program']) && $data['program'] instanceof Programme) {
            return static::generateForProgram($data['program'], $data);
        }

        if (!empty($data['programme_id']) || !empty($data['program_id'])) {
            $progId = $data['programme_id'] ?? $data['program_id'];
            $programme = Programme::find($progId);
            if ($programme) {
                return static::generateForProgram($programme, $data);
            }
        }

        return DB::transaction(function () use ($data) {
            $year = isset($data['year']) ? $data['year'] : date('y');
            $format = SystemSetting::get('matric_format', 'MIU{YEAR}{SEQUENCE}');

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
            if (stripos($format, '{SEQUENCE}') !== false) {
                if (isset($data['sequence'])) {
                    $sequence = $data['sequence'];
                } else {
                    // Calculate prefix for counting: replace all placeholders except SEQUENCE
                    $prefix = str_ireplace(
                        ['{YEAR}', '{RANDOM}', '{DEPT}', '{FACULTY}'],
                        [$year, '', $deptCode, $facCode],
                        $format
                    );
                    $prefix = str_ireplace('{SEQUENCE}', '', $prefix);

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
            }

            do {
                $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $matricNumber = str_ireplace(
                    ['{YEAR}', '{RANDOM}', '{SEQUENCE}', '{DEPT}', '{FACULTY}'],
                    [$year, $random, $sequence, $deptCode, $facCode],
                    $format
                );

                // If we are using sequence, we don't need to loop unless there's a collision
                if (stripos($format, '{RANDOM}') === false) {
                    // If sequence already exists, increment it
                    while (Student::where('matriculation_number', $matricNumber)->exists()) {
                        $maxCount++;
                        $seqCounter = str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                        $sequence = $startDigit . $seqCounter;
                        $matricNumber = str_ireplace(
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

    /**
     * Generate a unique matriculation number tailored to a specific Programme (UG, PGD, MBA, MIM, MSc, PhD).
     *
     * Resolution order for format template:
     * 1. $program->matric_format
     * 2. SystemSetting::get("matric_format_{$program->type}")
     * 3. SystemSetting::get("matric_format_{$program->academic_stream}")
     * 4. Global fallback template
     *
     * @param Programme $program
     * @param array|null $data
     * @return string
     */
    public static function generateForProgram(Programme $program, ?array $data = null): string
    {
        return DB::transaction(function () use ($program, $data) {
            $stream = $program->academic_stream ?: ($program->isPostgraduate() ? 'PG' : 'UG');
            $type = strtoupper($program->type ?: 'UG');

            // 1. Resolve template format
            $format = $program->matric_format
                ?: SystemSetting::get("matric_format_{$type}", null)
                ?: SystemSetting::get("matric_format_{$stream}", null);

            if (empty($format)) {
                $format = $program->isPostgraduate()
                    ? SystemSetting::get('matric_format_pg', 'MIU/{PROG_CODE}/{YEAR}/{SEQUENCE}')
                    : SystemSetting::get('matric_format', 'MIU{YEAR}{SEQUENCE}');
            }

            // 2. Resolve Year (e.g. 2026 or 26)
            if (isset($data['year'])) {
                $year = $data['year'];
            } else {
                $year = (stripos($format, '/') !== false || $program->isPostgraduate())
                    ? date('Y')
                    : date('y');
            }

            // 3. Resolve Program & Department codes
            $progCode = !empty($program->code)
                ? strtoupper($program->code)
                : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $program->name), 0, 3));

            $dept = $program->department;
            $deptCode = $data['dept_code'] ?? ($dept?->code ?: 'GEN');
            $facCode = $data['fac_code'] ?? ($dept?->faculty?->code ?: 'GEN');

            $level = isset($data['level']) ? (string) $data['level'] : ($program->isPostgraduate() ? '700' : '100');
            $startDigit = substr(trim($level), 0, 1);
            if (!in_array($startDigit, ['1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                $startDigit = '1';
            }

            // 4. Calculate Sequence Counter
            $hasSlashes = stripos($format, '/') !== false;
            $isPg = $program->isPostgraduate();

            $prefix = str_ireplace(
                ['{YEAR}', '{STREAM}', '{PROG_CODE}', '{DEPT_CODE}', '{DEPT}', '{FACULTY}', '{FAC_CODE}', '{RANDOM}'],
                [$year, $stream, $progCode, $deptCode, $deptCode, $facCode, $facCode, ''],
                $format
            );
            $prefix = str_ireplace('{SEQUENCE}', '', $prefix);

            $maxCount = 0;
            $existingMatrics = Student::where('matriculation_number', 'LIKE', $prefix . '%')
                ->lockForUpdate()
                ->pluck('matriculation_number');

            foreach ($existingMatrics as $matric) {
                $seqPart = substr($matric, strlen($prefix));
                if ($hasSlashes || $isPg) {
                    if (preg_match('/^(\d+)/', $seqPart, $matches)) {
                        $counterVal = (int) $matches[1];
                        if ($counterVal > $maxCount) {
                            $maxCount = $counterVal;
                        }
                    }
                } else {
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
            }

            if (isset($data['sequence'])) {
                $sequence = $data['sequence'];
            } else {
                if ($hasSlashes || $isPg) {
                    $sequence = str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $sequence = $startDigit . str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                }
            }

            do {
                $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $matricNumber = str_ireplace(
                    ['{YEAR}', '{STREAM}', '{PROG_CODE}', '{DEPT_CODE}', '{DEPT}', '{FACULTY}', '{FAC_CODE}', '{RANDOM}', '{SEQUENCE}'],
                    [$year, $stream, $progCode, $deptCode, $deptCode, $facCode, $facCode, $random, $sequence],
                    $format
                );

                if (stripos($format, '{RANDOM}') === false) {
                    while (Student::where('matriculation_number', $matricNumber)->exists()) {
                        $maxCount++;
                        if ($hasSlashes || $isPg) {
                            $sequence = str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                        } else {
                            $sequence = $startDigit . str_pad($maxCount + 1, 3, '0', STR_PAD_LEFT);
                        }
                        $matricNumber = str_ireplace(
                            ['{YEAR}', '{STREAM}', '{PROG_CODE}', '{DEPT_CODE}', '{DEPT}', '{FACULTY}', '{FAC_CODE}', '{SEQUENCE}'],
                            [$year, $stream, $progCode, $deptCode, $deptCode, $facCode, $facCode, $sequence],
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
