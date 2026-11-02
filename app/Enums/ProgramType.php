<?php

namespace App\Enums;

enum ProgramType: string
{
    case UG = 'UG';
    case PG = 'PG';
    case PGD = 'PGD';
    case MBA = 'MBA';
    case MIM = 'MIM';
    case MSC = 'MSC';
    case MPHIL = 'MPHIL';
    case PHD = 'PHD';
    case MASTERS = 'MASTERS';

    public function label(): string
    {
        return match ($this) {
            self::UG => 'Undergraduate (B.Sc / B.A)',
            self::PG => 'Postgraduate (General PG)',
            self::PGD => 'Postgraduate Diploma (PGD - 700L)',
            self::MBA => 'Master of Business Administration (MBA - 800L)',
            self::MIM => 'Master of International Management (MIM - 800L)',
            self::MSC => 'Academic Master\'s (M.Sc / M.A - 800L)',
            self::MPHIL => 'Master of Philosophy (M.Phil - 800L)',
            self::PHD => 'Doctor of Philosophy (Ph.D - 900L)',
            self::MASTERS => 'Master\'s Degree (800L)',
        };
    }

    public function isPostgraduate(): bool
    {
        return $this !== self::UG;
    }

    public function stream(): string
    {
        return $this->isPostgraduate() ? 'PG' : 'UG';
    }

    public function defaultEntryLevel(): int
    {
        return match ($this) {
            self::UG => 100,
            self::PGD => 700,
            self::PHD => 900,
            default => 800,
        };
    }

    public function defaultMinGraduationCgpa(): float
    {
        return $this->isPostgraduate() ? 3.00 : 1.00;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function postgraduateValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isPostgraduate())
        ));
    }

    public static function isPostgraduateValue(?string $value): bool
    {
        if (!$value) {
            return false;
        }

        $case = self::tryFrom(strtoupper(trim($value)));

        return $case ? $case->isPostgraduate() : false;
    }
}

