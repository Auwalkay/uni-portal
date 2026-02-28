<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubscriptionTier extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'max_students',
        'price',
    ];
}
