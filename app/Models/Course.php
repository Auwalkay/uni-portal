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
        'is_compulsory' => 'boolean',
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

    /**
     * Resolves whether this course is CORE (compulsory) or ELECTIVE for a student.
     */
    public function resolveIsCompulsoryFor(?Student $student, $overrides = null): bool
    {
        if (! $student) {
            return (bool) $this->is_compulsory;
        }

        // 1. Check explicit Programme curriculum override (from course_programme pivot)
        if ($overrides !== null && $overrides->has($this->id)) {
            return (bool) $overrides->get($this->id);
        }

        if ($student->program_id) {
            $pivot = \Illuminate\Support\Facades\DB::table('course_programme')
                ->where('programme_id', $student->program_id)
                ->where('course_id', $this->id)
                ->first();
            if ($pivot !== null) {
                return (bool) $pivot->is_compulsory;
            }
        }

        // 2. Explicitly flagged as compulsory on the course itself
        if ($this->is_compulsory) {
            return true;
        }

        // 3. Institutional General Studies (GNS / GST) are core university requirements
        $code = strtoupper(str_replace(' ', '', $this->code ?? ''));
        if (preg_match('/^(GNS|GST)\b/i', $this->code ?? '') || str_starts_with($code, 'GNS') || str_starts_with($code, 'GST')) {
            return true;
        }
        if ($this->relationLoaded('department') && $this->department && stripos($this->department->name, 'General Studies') !== false) {
            return true;
        }

        // 4. Programme or Department course for this student
        $studentDeptId = $student->department_id ?? $student->academicDepartment?->id ?? $student->program?->department_id;
        if ($this->programme_id && $student->program_id && $this->programme_id === $student->program_id) {
            return true;
        }
        if ($this->department_id && $studentDeptId && $this->department_id === $studentDeptId) {
            return true;
        }

        return false;
    }
}
