<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Programme extends Model
{
    use HasUuids;

    protected static function booted()
    {
        static::saving(function ($programme) {
            $isPgType = \App\Enums\ProgramType::isPostgraduateValue($programme->type);
            if (empty($programme->academic_stream) || $isPgType) {
                $programme->academic_stream = $isPgType ? 'PG' : ($programme->academic_stream ?: 'UG');
            }
            if (empty($programme->calendar_type)) {
                $programme->calendar_type = 'semester';
            }
            if (empty($programme->billing_model)) {
                $programme->billing_model = 'composite_term';
            }
            if (is_null($programme->min_graduation_cgpa) || ($programme->academic_stream === 'PG' && (float) $programme->min_graduation_cgpa < 3.00)) {
                $programme->min_graduation_cgpa = $programme->academic_stream === 'PG' ? 3.00 : 1.00;
            }
        });

        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $guarded = [];

    protected $casts = [
        'duration' => 'integer',
        'is_active' => 'boolean',
        'min_graduation_cgpa' => 'float',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_programme')
            ->withPivot('id', 'is_compulsory')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStream($query, string $stream)
    {
        $isPg = \App\Enums\ProgramType::isPostgraduateValue($stream);
        return $query->where('academic_stream', $isPg ? 'PG' : 'UG');
    }

    public function isPostgraduate(): bool
    {
        return strtoupper($this->academic_stream ?? '') === 'PG'
            || \App\Enums\ProgramType::isPostgraduateValue($this->type);
    }

    public function isUndergraduate(): bool
    {
        return ! $this->isPostgraduate();
    }

    public function usesUnbundledMilestones(): bool
    {
        return ($this->billing_model ?? 'composite_term') === 'unbundled_milestone';
    }

    public function usesCompositeTermBilling(): bool
    {
        return ! $this->usesUnbundledMilestones();
    }

    public function getEffectiveMinGraduationCgpa(): float
    {
        if (! is_null($this->min_graduation_cgpa)) {
            return (float) $this->min_graduation_cgpa;
        }

        return $this->isPostgraduate() ? 3.00 : 1.00;
    }
}
