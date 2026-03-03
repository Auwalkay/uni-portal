<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HostelBlock extends Model
{
    use HasUuids;

    protected $fillable = [
        'hostel_id',
        'name',
        'description',
    ];

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function floors()
    {
        return $this->hasMany(HostelFloor::class);
    }
}
