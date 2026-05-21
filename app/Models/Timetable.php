<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'semester_id',
        'department_id',
        'level',
        'course_id',
        'day',
        'start_time',
        'end_time',
        'venue'
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
}
