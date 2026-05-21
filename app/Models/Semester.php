<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_current' => 'boolean',
        'registration_starts_at' => 'date',
        'registration_ends_at' => 'date',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public static function current()
    {
        return \App\Services\AcademicCacheService::getCurrentSemester();
    }

    protected static function booted()
    {
        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }
}
