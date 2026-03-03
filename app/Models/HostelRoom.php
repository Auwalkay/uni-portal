<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HostelRoom extends Model
{
    use HasUuids;

    protected $fillable = [
        'hostel_floor_id',
        'room_number',
        'capacity',
    ];

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
