<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SickbayVisit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'attended_by', 'check_in_at', 'check_out_at', 
        'symptoms', 'visit_type', 'status', 'bed_number', 'admitted_to_bed_at'
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'admitted_to_bed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    public function medicalLog(): HasOne
    {
        return $this->hasOne(SickbayMedicalLog::class);
    }
}
