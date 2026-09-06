<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Hostel extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'name',
        'gender_type',
        'description',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function blocks()
    {
        return $this->hasMany(HostelBlock::class);
    }

    public function floors()
    {
        return $this->hasManyThrough(HostelFloor::class, HostelBlock::class);
    }

    public function fees()
    {
        return $this->hasMany(HostelFee::class);
    }
}
