<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Scholarship extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'percentage'];
}
