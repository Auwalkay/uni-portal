<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Programme;
use App\Models\Student;

class GradingService
{
    /**
     * Calculate Grade and Point based on Total Score and optional UG/PG context.
     *
     * Undergraduate Scale (Pass mark = 40%):
     * A: 70-100 (5.0), B: 60-69 (4.0), C: 50-59 (3.0), D: 45-49 (2.0), E: 40-44 (1.0), F: 0-39 (0.0)
     *
     * Postgraduate Scale (NUC Benchmark: Pass mark = 50%):
     * A: 70-100 (5.0 - Distinction), B: 60-69 (4.0 - Credit), C: 50-59 (3.0 - Pass), F: 0-49 (0.0 - Fail)
     *
     * @param  float|int  $totalScore
     * @param  mixed  $context  Optional Course, Student, Programme, CourseRegistration, string ('UG'|'PG'), or bool
     */
    public function calculate($totalScore, $context = null): array
    {
        if ($this->isPostgraduateContext($context)) {
            return $this->calculateForPostgraduate($totalScore);
        }

        return $this->calculateForUndergraduate($totalScore);
    }

    /**
     * Calculate Grade and Point using standard Undergraduate scale (40% pass mark).
     */
    public function calculateForUndergraduate($totalScore): array
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
     * Calculate Grade and Point using NUC Postgraduate scale (50% pass mark).
     */
    public function calculateForPostgraduate($totalScore): array
    {
        $score = round($totalScore);

        if ($score >= 70) {
            return ['grade' => 'A', 'point' => 5.0];
        } elseif ($score >= 60) {
            return ['grade' => 'B', 'point' => 4.0];
        } elseif ($score >= 50) {
            return ['grade' => 'C', 'point' => 3.0];
        } else {
            return ['grade' => 'F', 'point' => 0.0];
        }
    }

    /**
     * Determine whether the provided context belongs to a Postgraduate stream/course/student.
     */
    public function isPostgraduateContext($context = null): bool
    {
        if (is_null($context)) {
            return false;
        }

        if (is_bool($context)) {
            return $context;
        }

        if (is_string($context)) {
            return in_array(strtoupper(trim($context)), ['PG', 'PHD', 'MASTERS', 'PGD', 'MBA', 'MIM', 'MSC', 'MPHIL'], true);
        }

        if ($context instanceof Course) {
            return $context->isPostgraduate();
        }

        if ($context instanceof Programme) {
            return $context->isPostgraduate();
        }

        if ($context instanceof Student) {
            return $context->isPostgraduate();
        }

        if ($context instanceof CourseRegistration) {
            if ($context->relationLoaded('course') && $context->course && $context->course->isPostgraduate()) {
                return true;
            }
            if ($context->relationLoaded('student') && $context->student && $context->student->isPostgraduate()) {
                return true;
            }

            return (bool) ($context->course?->isPostgraduate() || $context->student?->isPostgraduate());
        }

        return false;
    }

    /**
     * Get the minimum passing score for a course/student context (50 for PG, 40 for UG).
     */
    public function getPassMark($context = null): int
    {
        return $this->isPostgraduateContext($context) ? 50 : 40;
    }

    /**
     * Get the minimum CGPA required for graduation (3.00 for PG, 1.00 for UG, or programme override).
     */
    public function getMinimumGraduationCgpa($context = null): float
    {
        if ($context instanceof Programme) {
            return $context->getEffectiveMinGraduationCgpa();
        }

        if ($context instanceof Student && $context->program) {
            return $context->program->getEffectiveMinGraduationCgpa();
        }

        return $this->isPostgraduateContext($context) ? 3.00 : 1.00;
    }

    /**
     * Check whether a given CGPA meets the graduation requirement for the context.
     */
    public function meetsGraduationRequirement(float $cgpa, $context = null): bool
    {
        return round($cgpa, 2) >= $this->getMinimumGraduationCgpa($context);
    }

    /**
     * Evaluate NUC Ph.D progression eligibility based on Master's CGPA.
     * - >= 3.50: Direct Ph.D eligibility
     * - 3.00 - 3.49: Requires M.Phil conversion first
     * - < 3.00: Ineligible
     */
    public function evaluatePhdProgression(float $mscCgpa): array
    {
        $cgpa = round($mscCgpa, 2);

        if ($cgpa >= 3.50) {
            return [
                'eligible_for_phd' => true,
                'requires_mphil_conversion' => false,
                'standing' => 'Ph.D Direct Progression',
            ];
        }

        if ($cgpa >= 3.00) {
            return [
                'eligible_for_phd' => false,
                'requires_mphil_conversion' => true,
                'standing' => 'M.Phil Conversion Required',
            ];
        }

        return [
            'eligible_for_phd' => false,
            'requires_mphil_conversion' => false,
            'standing' => 'Ineligible',
        ];
    }

    /**
     * Get human-readable academic standing for a CGPA and stream context.
     */
    public function getAcademicStanding(float $cgpa, $context = null): string
    {
        $cgpa = round($cgpa, 2);

        if ($this->isPostgraduateContext($context)) {
            if ($cgpa >= 4.50) {
                return 'Distinction';
            }
            if ($cgpa >= 3.50) {
                return 'Credit';
            }
            if ($cgpa >= $this->getMinimumGraduationCgpa($context)) {
                return 'Pass';
            }

            return 'Academic Probation';
        }

        if ($cgpa >= 4.50) {
            return 'First Class';
        }
        if ($cgpa >= 3.50) {
            return 'Second Class Upper';
        }
        if ($cgpa >= 2.40) {
            return 'Second Class Lower';
        }
        if ($cgpa >= 1.50) {
            return 'Third Class';
        }
        if ($cgpa >= 1.00) {
            return 'Pass';
        }

        return 'Probation';
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
