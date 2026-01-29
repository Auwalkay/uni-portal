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
    ];

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    public static function current()
    {
        return \Illuminate\Support\Facades\Cache::remember('current_session', 3600, function () {
            return self::where('is_current', true)->first();
        });
    }

    protected static function booted()
    {
        static::saved(function ($session) {
            \Illuminate\Support\Facades\Cache::forget('current_session');
        });
    }
}
