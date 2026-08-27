<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class HostelRoom extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'hostel_floor_id',
        'room_number',
        'capacity',
        'is_visible',
        'is_suspended',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_suspended' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function floor()
    {
        return $this->belongsTo(HostelFloor::class, 'hostel_floor_id');
    }

    public function bookings()
    {
        return $this->hasMany(HostelBooking::class);
    }

    public function hostel()
    {
        return $this->hasOneThrough(Hostel::class, HostelFloor::class, 'id', 'id', 'hostel_floor_id', 'hostel_id');
    }
}
