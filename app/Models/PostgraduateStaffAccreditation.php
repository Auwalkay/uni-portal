<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateStaffAccreditation extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'is_phd_accredited' => 'boolean',
        'is_masters_accredited' => 'boolean',
        'is_pgd_accredited' => 'boolean',
        'max_phd_quota' => 'integer',
        'max_masters_quota' => 'integer',
        'active_phd_count' => 'integer',
        'active_masters_count' => 'integer',
        'specialization_areas' => 'array',
        'accredited_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function accreditedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accredited_by_user_id');
    }

    public function hasPhdCapacity(): bool
    {
        return $this->is_phd_accredited && ($this->active_phd_count < $this->max_phd_quota);
    }

    public function hasMastersCapacity(): bool
    {
        return $this->is_masters_accredited && ($this->active_masters_count < $this->max_masters_quota);
    }

    public function recalculateActiveCounts(): void
    {
        $phdCount = PostgraduateResearchProject::where('major_supervisor_id', $this->staff_id)
            ->whereIn('status', ['topic_proposed', 'in_progress', 'defense_scheduled'])
            ->whereHas('student.program', fn ($q) => $q->where('type', 'PHD'))
            ->count();

        $mastersCount = PostgraduateResearchProject::where('major_supervisor_id', $this->staff_id)
            ->whereIn('status', ['topic_proposed', 'in_progress', 'defense_scheduled'])
            ->whereHas('student.program', fn ($q) => $q->whereIn('type', ['MSC', 'MBA', 'PGD']))
            ->count();

        $this->update([
            'active_phd_count' => $phdCount,
            'active_masters_count' => $mastersCount,
        ]);
    }
}

