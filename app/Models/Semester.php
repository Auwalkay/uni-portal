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
        return \Illuminate\Support\Facades\Cache::remember('current_semester', 3600, function () {
            return self::where('is_current', true)->with('session')->first();
        });
    }

    protected static function booted()
    {
        static::saved(function ($semester) {
            \Illuminate\Support\Facades\Cache::forget('current_semester');
            if ($semester->session_id) {
                \Illuminate\Support\Facades\Cache::forget('current_session');
            }
        });
    }
}
