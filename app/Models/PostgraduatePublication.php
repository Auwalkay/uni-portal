<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduatePublication extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_spgs_verified' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PostgraduateResearchProject::class, 'project_id');
    }
}

