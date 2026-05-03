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
        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }
}
