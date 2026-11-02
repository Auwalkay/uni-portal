<?php

namespace App\Services\Admissions;

use App\Models\Applicant;
use App\Models\ApplicantReferee;
use App\Models\Programme;
use Illuminate\Support\Str;

class PostgraduateAdmissionService
{
    public const QUALIFICATIONS = [
        'HND',
        'B.Sc',
        'B.A',
        'B.Eng',
        'B.Tech',
        'B.Ed',
        'LL.B',
        'PGD',
        'MBA',
        'MIM',
        'M.Sc',
        'M.A',
        'M.Eng',
        'M.Ed',
        'M.Phil',
    ];

    public const DEGREE_CLASSES = [
        'First Class',
        'Second Class Upper (2:1)',
        'Second Class Lower (2:2)',
        'Third Class',
        'Pass',
        'Distinction',
        'Upper Credit',
        'Lower Credit',
        'Merit',
        'Credit',
    ];

    /**
     * Resolve the PG tier for a given Programme:
     * - 'PGD' (700 Level)
     * - 'PROFESSIONAL_MASTERS' (MBA / MIM - 800 Level)
     * - 'ACADEMIC_MASTERS' (M.Sc / M.A / M.Eng / M.Ed - 800 Level)
     * - 'MPHIL' (M.Phil - 800/900 Level)
     * - 'PHD' (Ph.D - 900 Level)
     */
    public function resolveProgrammeTier(Programme $programme): string
    {
        $type = strtoupper(trim((string) $programme->type));
        $name = strtoupper(trim((string) $programme->name));
        $prefix = strtoupper(trim((string) $programme->matric_format));

        if (
            in_array($type, ['PHD', 'PH.D', 'DOCTORATE'], true) ||
            str_starts_with($prefix, 'PHD') ||
            preg_match('/\b(PH\.?D|DOCTOR)\b/i', $name)
        ) {
            return 'PHD';
        }

        if (
            in_array($type, ['MPHIL', 'M.PHIL'], true) ||
            str_starts_with($prefix, 'MPHIL') ||
            preg_match('/\bM\.?PHIL\b/i', $name)
        ) {
            return 'MPHIL';
        }

        if (
            $type === 'PGD' ||
            str_starts_with($prefix, 'PGD') ||
            preg_match('/\b(PGD|POSTGRADUATE DIPLOMA)\b/i', $name)
        ) {
            return 'PGD';
        }

        if (
            in_array($type, ['MBA', 'MIM', 'MPA', 'PROFESSIONAL_MASTERS'], true) ||
            str_starts_with($prefix, 'MBA') ||
            str_starts_with($prefix, 'MIM') ||
            preg_match('/\b(MBA|MIM|MPA|MASTER OF BUSINESS|MASTER OF INTERNATIONAL MANAGEMENT)\b/i', $name)
        ) {
            return 'PROFESSIONAL_MASTERS';
        }

        return 'ACADEMIC_MASTERS';
    }

    /**
     * Determine the default admission level for a programme.
     */
    public function resolveDefaultLevel(Programme $programme): int
    {
        if (!$programme->isPostgraduate()) {
            return 100;
        }

        return match ($this->resolveProgrammeTier($programme)) {
            'PGD' => 700,
            'PHD' => 900,
            default => 800,
        };
    }

    /**
     * Validate whether an applicant's previous qualification meets the NUC/SPGS entry requirements
     * for the target Postgraduate programme.
     *
     * Rules:
     * 1. PGD (700L): Accepts HND (Minimum Lower Credit) or Bachelor's (Third Class or higher).
     * 2. MBA / MIM (800L): Accepts Bachelor's (Minimum 2:2) OR PGD (Minimum 3.00 CGPA / Credit / Merit / Pass) OR Master's.
     *    Direct HND or Third Class without a PGD is rejected.
     * 3. M.Sc / Academic Master's (800L): Requires Bachelor's (Minimum 2:2) OR PGD (Minimum 3.00 CGPA).
     *    Direct HND or Third Class without a PGD is rejected.
     * 4. Ph.D (900L): Requires Academic Master's (M.Sc / M.A / M.Eng / M.Ed / M.Phil) with CGPA >= 3.50.
     *    If CGPA is 3.00–3.49, only eligible for M.Phil conversion (rejected for direct Ph.D).
     */
    public function validateEntryQualification(
        Programme $programme,
        string $qualification,
        string $classOfDegree,
        ?float $cgpa = null
    ): array {
        $tier = $this->resolveProgrammeTier($programme);
        $defaultLevel = $this->resolveDefaultLevel($programme);

        $qual = strtoupper(trim($qualification));
        $class = strtoupper(trim($classOfDegree));

        $isBachelors = in_array($qual, ['B.SC', 'BSC', 'B.A', 'BA', 'B.ENG', 'B.TECH', 'B.ED', 'LL.B', 'BACHELOR'], true);
        $isHnd = $qual === 'HND';
        $isPgd = $qual === 'PGD';
        $isAcademicMasters = in_array($qual, ['M.SC', 'MSC', 'M.A', 'MA', 'M.ENG', 'M.TECH', 'M.ED', 'M.PHIL', 'MPHIL'], true);
        $isProfessionalMasters = in_array($qual, ['MBA', 'MIM', 'MPA'], true);

        $isMinSecondClassLower = in_array($class, [
            'FIRST CLASS',
            'SECOND CLASS UPPER (2:1)',
            'SECOND CLASS UPPER',
            '2:1',
            'SECOND CLASS LOWER (2:2)',
            'SECOND CLASS LOWER',
            '2:2',
            'DISTINCTION',
        ], true);

        $isThirdClass = in_array($class, ['THIRD CLASS', '3RD CLASS'], true);
        $isPassDegree = $class === 'PASS';
        $isHndCredit = in_array($class, ['DISTINCTION', 'UPPER CREDIT', 'LOWER CREDIT', 'MERIT', 'CREDIT'], true);

        // 1. PGD (700 Level)
        if ($tier === 'PGD') {
            if ($isHnd && $isHndCredit) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for PGD admission via HND qualification.',
                ];
            }

            if ($isBachelors && ($isMinSecondClassLower || $isThirdClass)) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for PGD admission via Bachelor\'s degree.',
                ];
            }

            if ($isPgd || $isAcademicMasters || $isProfessionalMasters) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for PGD admission.',
                ];
            }

            return [
                'eligible' => false,
                'tier' => $tier,
                'default_level' => $defaultLevel,
                'conversion_required' => false,
                'message' => 'PGD admission requires a minimum of Third Class Bachelor\'s degree or HND (Lower Credit). Pass degrees are not eligible.',
            ];
        }

        // 2. Professional Master's: MBA / MIM (800 Level)
        if ($tier === 'PROFESSIONAL_MASTERS') {
            if ($isBachelors && $isMinSecondClassLower) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for Professional Master\'s (MBA/MIM) via Bachelor\'s degree (Min 2:2).',
                ];
            }

            if ($isPgd) {
                if ($cgpa !== null && $cgpa < 3.00) {
                    return [
                        'eligible' => false,
                        'tier' => $tier,
                        'default_level' => $defaultLevel,
                        'conversion_required' => false,
                        'message' => 'PGD holders applying for MBA/MIM must have a minimum PGD CGPA of 3.00.',
                    ];
                }

                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for Professional Master\'s (MBA/MIM) via Postgraduate Diploma (PGD).',
                ];
            }

            if ($isAcademicMasters || $isProfessionalMasters) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for Professional Master\'s (MBA/MIM).',
                ];
            }

            return [
                'eligible' => false,
                'tier' => $tier,
                'default_level' => $defaultLevel,
                'conversion_required' => false,
                'message' => 'MBA/MIM admission requires a Bachelor\'s degree (minimum Second Class Lower 2:2) or a Postgraduate Diploma (PGD). HND and Third Class holders must obtain a PGD first.',
            ];
        }

        // 3. Academic Master's: M.Sc / M.A / M.Ed (800 Level)
        if ($tier === 'ACADEMIC_MASTERS') {
            if ($isBachelors && $isMinSecondClassLower) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for Academic Master\'s (M.Sc) via Bachelor\'s degree (Min 2:2).',
                ];
            }

            if ($isPgd) {
                if ($cgpa !== null && $cgpa < 3.00) {
                    return [
                        'eligible' => false,
                        'tier' => $tier,
                        'default_level' => $defaultLevel,
                        'conversion_required' => false,
                        'message' => 'PGD holders applying for Academic Master\'s (M.Sc) must have a minimum CGPA of 3.00.',
                    ];
                }

                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for Academic Master\'s (M.Sc) via PGD (CGPA >= 3.00).',
                ];
            }

            if ($isAcademicMasters) {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for Academic Master\'s.',
                ];
            }

            return [
                'eligible' => false,
                'tier' => $tier,
                'default_level' => $defaultLevel,
                'conversion_required' => false,
                'message' => 'Academic Master\'s (M.Sc) requires a Bachelor\'s degree with at least Second Class Lower (2:2) or a PGD with minimum 3.00 CGPA. HND and Third Class holders must complete a PGD first.',
            ];
        }

        // 4. M.Phil (800/900 Level conversion pathway)
        if ($tier === 'MPHIL') {
            if (!$isAcademicMasters) {
                return [
                    'eligible' => false,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'M.Phil admission requires an Academic Master\'s degree (M.Sc / M.A) with a minimum CGPA of 3.00.',
                ];
            }

            if ($cgpa === null || $cgpa < 3.00) {
                return [
                    'eligible' => false,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'M.Phil admission requires a minimum Master\'s CGPA of 3.00 on a 5.00 scale.',
                ];
            }

            return [
                'eligible' => true,
                'tier' => $tier,
                'default_level' => $defaultLevel,
                'conversion_required' => false,
                'message' => 'Eligible for M.Phil programme.',
            ];
        }

        // 5. Doctorate: Ph.D (900 Level)
        if ($tier === 'PHD') {
            if (!$isAcademicMasters) {
                return [
                    'eligible' => false,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Ph.D admission requires an Academic Master\'s degree (M.Sc, M.A, M.Eng, or M.Phil). Professional Master\'s (MBA/MIM) or Bachelor\'s degrees do not qualify directly for Ph.D.',
                ];
            }

            if ($qual === 'M.PHIL' || $qual === 'MPHIL') {
                return [
                    'eligible' => true,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Eligible for direct Ph.D admission via M.Phil qualification.',
                ];
            }

            if ($cgpa === null || $cgpa < 3.00) {
                return [
                    'eligible' => false,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => false,
                    'message' => 'Ph.D admission requires a minimum Master\'s CGPA of 3.50 (or 3.00–3.49 for M.Phil conversion). Scores below 3.00 do not qualify.',
                ];
            }

            if ($cgpa < 3.50) {
                return [
                    'eligible' => false,
                    'tier' => $tier,
                    'default_level' => $defaultLevel,
                    'conversion_required' => true,
                    'message' => 'Direct Ph.D admission requires a minimum Master\'s CGPA of 3.50 / 5.00. With a CGPA of ' . number_format($cgpa, 2) . ' (between 3.00 and 3.49), you must apply for the M.Phil / Ph.D conversion pathway first.',
                ];
            }

            return [
                'eligible' => true,
                'tier' => $tier,
                'default_level' => $defaultLevel,
                'conversion_required' => false,
                'message' => 'Eligible for direct Ph.D admission (M.Sc CGPA >= 3.50).',
            ];
        }

        return [
            'eligible' => true,
            'tier' => $tier,
            'default_level' => $defaultLevel,
            'conversion_required' => false,
            'message' => 'Eligible.',
        ];
    }

    /**
     * Evaluate a list of prior academic qualifications (e.g. HND + PGD, or B.Sc + M.Sc)
     * and determine if any qualification in the applicant's academic history satisfies
     * the target Postgraduate programme's NUC/SPGS entry requirements.
     */
    public function validateQualificationsList(Programme $programme, array $qualifications): array
    {
        if (empty($qualifications)) {
            return [
                'eligible' => false,
                'tier' => $this->resolveProgrammeTier($programme),
                'default_level' => $this->resolveDefaultLevel($programme),
                'conversion_required' => false,
                'message' => 'At least one prior academic qualification is required for Postgraduate admission.',
            ];
        }

        $lastResult = null;
        $conversionCandidate = null;

        foreach ($qualifications as $item) {
            $qualType = (string) ($item['qualification_type'] ?? $item['previous_qualification'] ?? '');
            $classOfDegree = (string) ($item['class_of_degree'] ?? '');
            $cgpa = isset($item['cgpa']) && $item['cgpa'] !== '' ? (float) $item['cgpa'] : null;

            $result = $this->validateEntryQualification($programme, $qualType, $classOfDegree, $cgpa);
            $result['matched_qualification'] = $item;

            if ($result['eligible']) {
                return $result;
            }

            if (!empty($result['conversion_required'])) {
                $conversionCandidate = $result;
            }

            $lastResult = $result;
        }

        return $conversionCandidate ?? $lastResult;
    }

    /**
     * Synchronize repeatable tertiary qualifications for a Postgraduate applicant
     * and mirror the highest/qualifying degree to the summary columns on `applicants`.
     */
    public function syncQualifications(Applicant $applicant, array $qualifications): void
    {
        $applicant->qualifications()->getQuery()->delete();

        $rankMap = [
            'PH.D' => 6,
            'PHD' => 6,
            'M.PHIL' => 5,
            'MPHIL' => 5,
            'M.SC' => 4,
            'MSC' => 4,
            'M.A' => 4,
            'M.ENG' => 4,
            'M.TECH' => 4,
            'M.ED' => 4,
            'MBA' => 3,
            'MIM' => 3,
            'MPA' => 3,
            'PGD' => 2,
            'B.SC' => 1,
            'B.A' => 1,
            'B.ENG' => 1,
            'B.TECH' => 1,
            'B.ED' => 1,
            'LL.B' => 1,
            'HND' => 0,
        ];

        $highestIndex = 0;
        $highestRank = -1;

        foreach ($qualifications as $idx => $qual) {
            $typeKey = strtoupper(trim((string) ($qual['qualification_type'] ?? '')));
            $rank = $rankMap[$typeKey] ?? 0;
            if ($rank >= $highestRank) {
                $highestRank = $rank;
                $highestIndex = $idx;
            }
        }

        foreach ($qualifications as $idx => $qual) {
            $isHighest = ($idx === $highestIndex);
            $applicant->qualifications()->create([
                'qualification_type' => $qual['qualification_type'],
                'institution_name' => $qual['institution_name'],
                'course_of_study' => $qual['course_of_study'],
                'class_of_degree' => $qual['class_of_degree'],
                'cgpa' => isset($qual['cgpa']) && $qual['cgpa'] !== '' ? (float) $qual['cgpa'] : null,
                'scale_max' => isset($qual['scale_max']) && $qual['scale_max'] !== '' ? (float) $qual['scale_max'] : 5.00,
                'graduation_year' => (int) $qual['graduation_year'],
                'certificate_number' => $qual['certificate_number'] ?? null,
                'is_highest_qualification' => $isHighest,
                'sort_order' => $idx + 1,
            ]);

            if ($isHighest) {
                $applicant->update([
                    'previous_qualification' => $qual['qualification_type'],
                    'previous_institution' => $qual['institution_name'],
                    'previous_course_of_study' => $qual['course_of_study'],
                    'previous_class_of_degree' => $qual['class_of_degree'],
                    'previous_cgpa' => isset($qual['cgpa']) && $qual['cgpa'] !== '' ? (float) $qual['cgpa'] : null,
                    'graduation_year' => (int) $qual['graduation_year'],
                ]);
            }
        }
    }

    /**
     * Synchronize repeatable employment records for a Postgraduate applicant.
     */
    public function syncEmployments(Applicant $applicant, array $employments): void
    {
        $applicant->employments()->getQuery()->delete();

        foreach ($employments as $emp) {
            if (empty($emp['employer_name']) || empty($emp['job_title'])) {
                continue;
            }

            $applicant->employments()->create([
                'employer_name' => $emp['employer_name'],
                'job_title' => $emp['job_title'],
                'department_or_unit' => $emp['department_or_unit'] ?? null,
                'start_date' => !empty($emp['start_date']) ? $emp['start_date'] : null,
                'end_date' => !empty($emp['end_date']) ? $emp['end_date'] : null,
                'is_current' => (bool) ($emp['is_current'] ?? false),
                'responsibilities' => $emp['responsibilities'] ?? null,
            ]);
        }
    }

    /**
     * Synchronize the 3 online referees for a Postgraduate applicant.
     * Preserves already-submitted referee records if the email matches.
     */
    public function syncReferees(Applicant $applicant, array $referees): void
    {
        $existingByEmail = $applicant->referees()
            ->get()
            ->keyBy(fn (ApplicantReferee $r) => strtolower(trim($r->email)));

        $keptIds = [];

        foreach ($referees as $refData) {
            $emailKey = strtolower(trim($refData['email']));
            $existing = $existingByEmail->get($emailKey);

            if ($existing) {
                $existing->update([
                    'name' => $refData['name'],
                    'phone' => $refData['phone'] ?? $existing->phone,
                    'institution_or_organization' => $refData['institution_or_organization'],
                    'position_or_rank' => $refData['position_or_rank'] ?? $existing->position_or_rank,
                    'relationship' => $refData['relationship'] ?? $existing->relationship,
                ]);
                $keptIds[] = $existing->id;
            } else {
                $created = $applicant->referees()->create([
                    'name' => $refData['name'],
                    'email' => trim($refData['email']),
                    'phone' => $refData['phone'] ?? null,
                    'institution_or_organization' => $refData['institution_or_organization'],
                    'position_or_rank' => $refData['position_or_rank'] ?? null,
                    'relationship' => $refData['relationship'] ?? null,
                    'token' => Str::random(64),
                    'status' => 'pending',
                    'expires_at' => now()->addDays(30),
                ]);
                $keptIds[] = $created->id;
            }
        }

        if (!empty($keptIds)) {
            $applicant->referees()->whereNotIn('id', $keptIds)->delete();
        }
    }
}
