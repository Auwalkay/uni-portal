<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'registration_starts_at' => 'date',
        'registration_ends_at' => 'date',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public static function current()
    {
        return \App\Services\AcademicCacheService::getCurrentSemester();
    }

    /**
     * Get the standardized semester code ('1', '2', '3') from the semester name.
     */
    public function getSemesterCode(): string
    {
        $name = strtolower($this->name ?? '');

        if (str_contains($name, 'first') || str_contains($name, '1st') || $name === '1') {
            return '1';
        }

        if (str_contains($name, 'second') || str_contains($name, '2nd') || $name === '2') {
            return '2';
        }

        if (str_contains($name, 'third') || str_contains($name, '3rd') || str_contains($name, 'summer') || $name === '3') {
            return '3';
        }

        return '1';
    }

    /**
     * Get matching course semester values stored across legacy or modern course records.
     *
     * @return array<int, string>
     */
    public function getMatchingCourseSemesters(): array
    {
        return match ($this->getSemesterCode()) {
            '1' => ['1', 'First', 'First Semester'],
            '2' => ['2', 'Second', 'Second Semester'],
            '3' => ['3', 'Third', 'Third Semester', 'Summer', 'Summer Semester'],
            default => ['1', 'First', 'First Semester'],
        };
    }

    protected static function booted()
    {
        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }
}
