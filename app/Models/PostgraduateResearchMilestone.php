<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateResearchMilestone extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $appends = [
        'submission_path',
        'stage_order',
    ];

    public function getSubmissionPathAttribute(): ?string
    {
        return $this->document_path;
    }

    public function getStageOrderAttribute(): int
    {
        return $this->sequence_order ?? 1;
    }

    protected $casts = [
        'sequence_order' => 'integer',
        'plagiarism_similarity_index' => 'float',
        'score' => 'float',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PostgraduateResearchProject::class, 'project_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isCleared(): bool
    {
        return in_array($this->status, ['supervisor_approved', 'spgs_cleared'], true);
    }
}

