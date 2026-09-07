<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExamIncident extends Model
{
    use HasUuids;

    protected $fillable = [
        'reference_id',
        'exam_schedule_id',
        'student_id',
        'invigilator_id',
        'incident_type',
        'description',
        'status',
        'action_taken',
        'logged_by',
    ];

    public function schedule()
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function invigilator()
    {
        return $this->belongsTo(Staff::class, 'invigilator_id');
    }

    public function logger()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
