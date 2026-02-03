<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Course;
use App\Models\Staff;
use App\Models\Session;

class CourseAllocation extends Model
{
    use HasUuids;

    protected $fillable = ['course_id', 'staff_id', 'session_id', 'department_id', 'program_id'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }
}
