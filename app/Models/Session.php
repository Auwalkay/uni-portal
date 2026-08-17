<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasUuids;

    protected $table = 'academic_sessions';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'registration_enabled' => 'boolean',
        'applications_enabled' => 'boolean',
        'admissions_enabled' => 'boolean',
        'type' => 'string',
        'late_payment_deadline' => 'datetime',
        'school_fee_payment_enabled' => 'boolean',
        'late_fee_amount' => 'double',
    ];

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    public function studentSessions()
    {
        return $this->hasMany(StudentSession::class);
    }

    public function feeConfigurations()
    {
        return $this->hasMany(FeeConfiguration::class);
    }

    public static function current()
    {
        return \App\Services\AcademicCacheService::getCurrentSession();
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
