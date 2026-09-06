<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'reference_id',
        'session_id',
        'semester_id',
        'department_id',
        'level',
        'course_id',
        'exam_date',
        'start_time',
        'end_time',
        'venue',
        'exam_type',
        'max_capacity',
        'instructions',
        'created_by',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invigilators()
    {
        return $this->hasMany(ExamInvigilator::class);
    }

    public function incidents()
    {
        return $this->hasMany(ExamIncident::class);
    }

    public function attendances()
    {
        return $this->hasMany(ExamAttendance::class);
    }
}
