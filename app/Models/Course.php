<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    // Alias for consistent naming if needed, or just use programme
    public function program()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }
}
