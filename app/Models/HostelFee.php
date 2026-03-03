<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HostelFee extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'hostel_id',
        'amount',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }
}
