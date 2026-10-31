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
        'academic_stream' => 'string',
        'calendar_type' => 'string',
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

    /**
     * Resolve the active session matching the student's programme stream and calendar mode
     */
    public static function currentForProgram(Programme $program): ?Session
    {
        $stream = $program->academic_stream ?: ($program->isPostgraduate() ? 'PG' : 'UG');
        $calendar = $program->calendar_type ?: 'semester';

        return static::currentForStream($stream, $calendar) ?: static::current();
    }

    /**
     * Resolve active session for a specific academic stream and calendar type
     */
    public static function currentForStream(string $stream = 'UG', string $calendarType = 'semester'): ?Session
    {
        return \App\Services\AcademicCacheService::getCurrentSessionForStream($stream, $calendarType);
    }

    public function scopeStream($query, string $stream)
    {
        return $query->where('academic_stream', strtoupper($stream));
    }

    public function isPostgraduate(): bool
    {
        return strtoupper($this->academic_stream ?? 'UG') === 'PG';
    }

    public function isUndergraduate(): bool
    {
        return ! $this->isPostgraduate();
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->academic_stream)) {
                $model->academic_stream = 'UG';
            }
            if (empty($model->calendar_type)) {
                $model->calendar_type = 'semester';
            }
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
