<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_one_time'];

    protected $casts = [
        'is_one_time' => 'boolean',
    ];

    public function configurations()
    {
        return $this->hasMany(FeeConfiguration::class);
    }
}
