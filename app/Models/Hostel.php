<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Hostel extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'gender_type',
        'description',
    ];

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
