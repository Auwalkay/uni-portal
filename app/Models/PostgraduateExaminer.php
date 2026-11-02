<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateExaminer extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'draft_dispatched_at' => 'date',
        'report_received_at' => 'date',
        'honorarium_paid' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PostgraduateResearchProject::class, 'project_id');
    }
}

