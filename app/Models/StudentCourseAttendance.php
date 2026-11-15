<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StudentCourseAttendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'course_registration_id',
        'student_id',
        'course_id',
        'session_id',
        'attendance_date',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'status' => AttendanceStatus::class,
        'attendance_date' => 'date:Y-m-d',
    ];

    public function courseRegistration()
    {
        return $this->belongsTo(CourseRegistration::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
