<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateCandidacyRequest extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'extension_months' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PostgraduateResearchProject::class, 'project_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}

