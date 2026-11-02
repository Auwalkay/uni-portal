<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasUuids;

    public const COURSE_TYPES = [
        'lecture_exam' => 'Standard Lecture & Examination',
        'case_study_seminar' => 'Executive Case Study & Seminar',
        'practicum_internship' => 'Industry Practicum / Attachment',
        'capstone_project' => 'Capstone / Applied Project',
        'academic_dissertation' => 'Academic Thesis / Dissertation',
    ];

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
            if (empty($course->course_type)) {
                $course->course_type = 'lecture_exam';
            }
        });

        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
        'units' => 'integer',
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

    public function allocationRequests()
    {
        return $this->hasMany(CourseAllocationRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isPostgraduate(): bool
    {
        if ((int) ($this->level ?? 0) >= 700) {
            return true;
        }

        if ($this->relationLoaded('programme') && $this->programme) {
            return $this->programme->isPostgraduate();
        }

        if ($this->programme_id) {
            return (bool) $this->programme?->isPostgraduate();
        }

        return false;
    }

    public function isResearchOrProject(): bool
    {
        return in_array($this->course_type ?? 'lecture_exam', [
            'case_study_seminar',
            'practicum_internship',
            'capstone_project',
            'academic_dissertation',
        ], true);
    }

    public function getCourseTypeLabelAttribute(): string
    {
        return self::COURSE_TYPES[$this->course_type ?? 'lecture_exam'] ?? 'Standard Lecture & Examination';
    }
}
