<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function configurations()
    {
        return $this->hasMany(FeeConfiguration::class);
    }
}
