<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostgraduateResearchProject extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'defense_date' => 'datetime',
        'approved_at' => 'datetime',
        'final_score' => 'float',
        'final_grade_point' => 'float',
    ];

    public const PATHWAY_TYPES = [
        'pgd_project' => 'PGD Long Essay / Project (700 Level)',
        'capstone_consulting' => 'Professional Executive Capstone / Practicum (MBA / MIM)',
        'academic_dissertation' => 'Academic Thesis / Dissertation (M.Sc / M.Phil / Ph.D)',
    ];

    public const DEFAULT_MILESTONES_BY_PATHWAY = [
        'pgd_project' => [
            ['stage' => 'concept_note', 'title' => 'Project Topic & Concept Note Approval', 'sequence_order' => 1],
            ['stage' => 'draft_report', 'title' => 'Complete Draft Project Report', 'sequence_order' => 2],
            ['stage' => 'internal_review', 'title' => 'Internal Departmental Review & Assessment', 'sequence_order' => 3],
            ['stage' => 'final_bound_copy', 'title' => 'Final Corrected Bound Copy Submission', 'sequence_order' => 4],
        ],
        'capstone_consulting' => [
            ['stage' => 'case_proposal', 'title' => 'Executive Case / Consulting Proposal', 'sequence_order' => 1],
            ['stage' => 'practicum_report', 'title' => 'Industry Practicum / Organizational Diagnostic Report', 'sequence_order' => 2],
            ['stage' => 'capstone_draft', 'title' => 'Full Capstone Consulting Report & Similarity Check', 'sequence_order' => 3],
            ['stage' => 'panel_presentation', 'title' => 'Executive Panel Presentation & Defense', 'sequence_order' => 4],
            ['stage' => 'final_submission', 'title' => 'Final Executive Repository Submission', 'sequence_order' => 5],
        ],
        'academic_dissertation' => [
            ['stage' => 'synopsis_proposal', 'title' => 'Research Synopsis & Title Registration', 'sequence_order' => 1],
            ['stage' => 'proposal_seminar', 'title' => 'Pre-Data Proposal Seminar Defense', 'sequence_order' => 2],
            ['stage' => 'post_data_seminar', 'title' => 'Post-Data / Findings Seminar Defense', 'sequence_order' => 3],
            ['stage' => 'pre_defense_draft', 'title' => 'Full Thesis Draft & Plagiarism Clearance (<= 20%)', 'sequence_order' => 4],
            ['stage' => 'viva_voce_defense', 'title' => 'External Oral Examination (Viva Voce Defense)', 'sequence_order' => 5],
            ['stage' => 'hardbound_repository', 'title' => 'Hardbound Thesis & SPGS Repository Clearance', 'sequence_order' => 6],
        ],
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseRegistration(): BelongsTo
    {
        return $this->belongsTo(CourseRegistration::class);
    }

    public function majorSupervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'major_supervisor_id');
    }

    public function coSupervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'co_supervisor_id');
    }

    public function secondCoSupervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'co_supervisor_2_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PostgraduateResearchMilestone::class, 'project_id')->orderBy('sequence_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PostgraduateResearchLog::class, 'project_id')->orderByDesc('entry_date');
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(PostgraduateProgressReport::class, 'project_id')->latest();
    }

    public function candidacyRequests(): HasMany
    {
        return $this->hasMany(PostgraduateCandidacyRequest::class, 'project_id')->latest();
    }

    public function publications(): HasMany
    {
        return $this->hasMany(PostgraduatePublication::class, 'project_id')->latest();
    }

    public function examiners(): HasMany
    {
        return $this->hasMany(PostgraduateExaminer::class, 'project_id');
    }

    public function initializeDefaultMilestones(): void
    {
        $templates = self::DEFAULT_MILESTONES_BY_PATHWAY[$this->pathway_type]
            ?? self::DEFAULT_MILESTONES_BY_PATHWAY['academic_dissertation'];

        foreach ($templates as $tpl) {
            $this->milestones()->firstOrCreate(
                ['stage' => $tpl['stage']],
                [
                    'title' => $tpl['title'],
                    'sequence_order' => $tpl['sequence_order'],
                    'status' => 'pending',
                ]
            );
        }
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->milestones()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->milestones()
            ->whereIn('status', ['supervisor_approved', 'spgs_cleared'])
            ->count();

        return (int) round(($completed / $total) * 100);
    }
}

