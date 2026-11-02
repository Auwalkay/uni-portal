<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class FeeConfiguration extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
    public const MILESTONE_TYPES = [
        'bench_fee' => 'Research Bench / Laboratory Fee',
        'practicum_fee' => 'Executive Practicum / Field Attachment Fee',
        'defense_fee' => 'External Defense / Viva Voce Fee',
        'extension_fee' => 'Programme Extension / Renewal Fee',
    ];

    protected $fillable = [
        'fee_type_id',
        'session_id',
        'semester_id',
        'faculty_id',
        'department_id',
        'program_id',
        'level',
        'entry_mode',
        'amount',
        'is_compulsory',
        'is_per_course',
        'is_milestone_fee',
        'milestone_type',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_compulsory' => 'boolean',
        'is_per_course' => 'boolean',
        'is_milestone_fee' => 'boolean',
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function program()
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }
}
