<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Attendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'staff_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
