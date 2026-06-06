<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected static function booted()
    {
        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $guarded = [];

    public function lgas()
    {
        return $this->hasMany(Lga::class);
    }
}
