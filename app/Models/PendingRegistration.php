<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'reference',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
