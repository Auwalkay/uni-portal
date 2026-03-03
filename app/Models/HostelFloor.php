<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HostelFloor extends Model
{
    use HasUuids;

    protected $fillable = [
        'hostel_block_id',
        'name',
    ];

    public function block()
    {
        return $this->belongsTo(HostelBlock::class, 'hostel_block_id');
    }

    public function rooms()
    {
        return $this->hasMany(HostelRoom::class);
    }
}
