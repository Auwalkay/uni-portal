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
            if (empty($programme->academic_stream)) {
                $programme->academic_stream = in_array(strtoupper($programme->type ?? 'UG'), ['PG', 'PHD', 'MASTERS', 'PGD', 'MBA', 'MIM']) ? 'PG' : 'UG';
            }
            if (empty($programme->calendar_type)) {
                $programme->calendar_type = 'semester';
            }
        });

        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $guarded = [];

    protected $casts = [
        'duration' => 'integer',
        'is_active' => 'boolean',
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
        $isPg = in_array(strtoupper($stream), ['PG', 'PHD', 'MASTERS', 'PGD', 'MBA', 'MIM']);
        return $query->where('academic_stream', $isPg ? 'PG' : 'UG');
    }

    public function isPostgraduate(): bool
    {
        return strtoupper($this->academic_stream ?? '') === 'PG'
            || in_array(strtoupper($this->type ?? ''), ['PG', 'PHD', 'MASTERS', 'PGD', 'MBA', 'MIM']);
    }

    public function isUndergraduate(): bool
    {
        return ! $this->isPostgraduate();
    }
}
