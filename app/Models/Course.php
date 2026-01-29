<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
