<?php

namespace App\Services;

class GradingService
{
    /**
     * Calculate Grade and Point based on Total Score.
     * Scale:
     * A: 70-100 (5.0)
     * B: 60-69 (4.0)
     * C: 50-59 (3.0)
     * D: 45-49 (2.0)
     * E: 40-44 (1.0)
     * F: 0-39 (0.0)
     */
    public function calculate($totalScore): array
    {
        $score = round($totalScore);

        if ($score >= 70) {
            return ['grade' => 'A', 'point' => 5.0];
        } elseif ($score >= 60) {
            return ['grade' => 'B', 'point' => 4.0];
        } elseif ($score >= 50) {
            return ['grade' => 'C', 'point' => 3.0];
        } elseif ($score >= 45) {
            return ['grade' => 'D', 'point' => 2.0];
        } elseif ($score >= 40) {
            return ['grade' => 'E', 'point' => 1.0];
        } else {
            return ['grade' => 'F', 'point' => 0.0];
        }
    }

    /**
     * Calculate GPA from a collection of CourseRegistrations.
     * GPA = Sum(Unit * Point) / Sum(Unit)
     */
    public function calculateGPA($registrations): float
    {
        $totalQualityPoints = 0;
        $totalUnits = 0;

        foreach ($registrations as $reg) {
            // Ensure we have course relationship loaded
            if ($reg->course) {
                $totalQualityPoints += ($reg->course->units * $reg->grade_point);
                $totalUnits += $reg->course->units;
            }
        }

        if ($totalUnits === 0) {
            return 0.00;
        }

        return round($totalQualityPoints / $totalUnits, 2);
    }
}
