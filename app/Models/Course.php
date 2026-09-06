<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasUuids;

    protected static function booted()
    {
        static::saving(function ($course) {
            if ($course->code) {
                $cleaned = str_replace(' ', '', $course->code);
                $cleaned = strtoupper($cleaned);
                if (preg_match('/^([A-Z\-]+)(\d+.*)$/', $cleaned, $matches)) {
                    $course->code = $matches[1] . ' ' . $matches[2];
                } else {
                    $course->code = $cleaned;
                }
            }
        });

        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    // Alias for consistent naming if needed, or just use programme
    public function program()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function allocations()
    {
        return $this->hasMany(CourseAllocation::class);
    }
}
