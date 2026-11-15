<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
        };
    }

    /**
     * Normalize flexible input strings (e.g. from Excel/CSV) to AttendanceStatus.
     */
    public static function fromInput(?string $value): self
    {
        if ($value === null) {
            return self::ABSENT;
        }

        $cleaned = strtolower(trim($value));

        return match ($cleaned) {
            'present', 'p', '1', 'yes', 'true' => self::PRESENT,
            default => self::ABSENT,
        };
    }
}
