<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasUuids;

    protected $fillable = [
        'reference_id',
        'title',
        'session_id',
        'semester_id',
        'exam_type',
        'start_date',
        'end_date',
        'is_published',
        'is_locked',
        'docket_printing_enabled',
        'instructions',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
        'docket_printing_enabled' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schedules()
    {
        return $this->hasMany(ExamSchedule::class);
    }

    public function incidents()
    {
        return $this->hasManyThrough(ExamIncident::class, ExamSchedule::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
