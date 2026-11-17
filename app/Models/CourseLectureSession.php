<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLectureSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'lecture_date' => 'date:Y-m-d',
        'department_ids' => 'array',
        'department_names' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentCourseAttendance::class, 'course_lecture_session_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Determine if this lecture session was scheduled for a given department.
     */
    public function appliesToDepartment(?string $departmentId): bool
    {
        if (empty($this->department_ids)) {
            return true; // All departments
        }

        if (empty($departmentId)) {
            return false;
        }

        return in_array($departmentId, $this->department_ids, true);
    }
}
