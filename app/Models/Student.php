<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'program_duration' => 'integer',
    ];

    public function admittedSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'admitted_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
