<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SickbayMedicalLog extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'sickbay_visit_id', 'blood_pressure', 'temperature', 'weight', 
        'findings', 'treatment_given', 'medicines_dispensed',
        'parent_contacted', 'parent_contacted_at', 'parent_contact_notes',
        'referral_hospital', 'referral_notes',
        'recommended_tests', 'external_prescriptions'
    ];

    protected $casts = [
        'parent_contacted' => 'boolean',
        'parent_contacted_at' => 'datetime',
        'medicines_dispensed' => 'array',
        'recommended_tests' => 'array',
        'external_prescriptions' => 'array',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(SickbayVisit::class, 'sickbay_visit_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
