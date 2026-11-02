<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateProgressReport extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'completion_percentage' => 'integer',
        'supervisor_signed_at' => 'datetime',
        'dpgc_approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PostgraduateResearchProject::class, 'project_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function dpgcApprover(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'dpgc_approved_by_staff_id');
    }
}

