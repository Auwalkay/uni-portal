<?php

namespace App\Services;

use App\Models\Programme;

class AdmissionLetterService
{
    public const TIER_PGD = 'pgd';
    public const TIER_MASTERS = 'masters';
    public const TIER_PHD = 'phd';

    /**
     * Resolve the Blade view name for an admission letter.
     */
    public function resolveView(bool $isPostgraduate): string
    {
        return $isPostgraduate
            ? 'documents.pg_admission_letter'
            : 'documents.admission_letter';
    }

    /**
     * Resolve the postgraduate admission letter tier ('pgd', 'masters', or 'phd').
     */
    public function resolvePgTier(?Programme $programme): string
    {
        $type = strtoupper((string) ($programme?->type ?? ''));
        $name = strtoupper((string) ($programme?->name ?? ''));

        if ($type === 'PHD' || str_contains($name, 'PH.D') || str_contains($name, 'DOCTOR OF PHILOSOPHY')) {
            return self::TIER_PHD;
        }

        if ($type === 'PGD' || str_starts_with($name, 'PGD') || str_contains($name, 'POSTGRADUATE DIPLOMA')) {
            return self::TIER_PGD;
        }

        return self::TIER_MASTERS;
    }

    /**
     * Resolve the degree label used in the Masters / PGD / Ph.D. letter heading and intro.
     */
    public function resolveDegreeLabel(?Programme $programme, string $tier): string
    {
        if ($tier === self::TIER_PHD) {
            return 'Doctor of Philosophy (Ph.D.)';
        }

        if ($tier === self::TIER_PGD) {
            return 'Postgraduate Diploma (PGD)';
        }

        $type = strtoupper((string) ($programme?->type ?? ''));
        $name = (string) ($programme?->name ?? '');

        if ($type === 'MBA' || str_contains(strtoupper($name), 'MBA')) {
            return 'Master of Business Administration (MBA)';
        }

        if (str_contains(strtoupper($name), 'MIPSA')) {
            return 'Master in Public Sector Accounting (MIPSA)';
        }

        if (str_contains(strtoupper($name), 'M.ENG')) {
            return 'Master of Engineering (M.Eng.)';
        }

        if (str_contains(strtoupper($name), 'LL.M')) {
            return 'Master of Laws (LL.M.)';
        }

        return 'Master of Science (M.Sc.)';
    }
}
