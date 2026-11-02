<?php

namespace App\Services\Postgraduate;

use App\Models\PostgraduateExternalExaminerRegistry;
use App\Models\PostgraduateResearchLog;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Models\PostgraduateStaffAccreditation;
use App\Models\Session;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostgraduateStaffGovernanceService
{
    public const DEFAULT_PHD_QUOTA = 5;
    public const DEFAULT_MASTERS_QUOTA = 8;
    public const SLA_TURNAROUND_DAYS = 14;

    /**
     * Get or create accreditation record for an academic staff member.
     */
    public function getOrCreateAccreditation(Staff $staff): PostgraduateStaffAccreditation
    {
        return PostgraduateStaffAccreditation::firstOrCreate(
            ['staff_id' => $staff->id],
            [
                'is_phd_accredited' => str_contains(strtolower((string) $staff->designation), 'prof') || str_contains(strtolower((string) $staff->designation), 'senior lecturer'),
                'is_masters_accredited' => true,
                'is_pgd_accredited' => true,
                'max_phd_quota' => self::DEFAULT_PHD_QUOTA,
                'max_masters_quota' => self::DEFAULT_MASTERS_QUOTA,
                'active_phd_count' => 0,
                'active_masters_count' => 0,
            ]
        );
    }

    /**
     * Check if a supervisor has capacity for a specific candidate project.
     */
    public function validateSupervisorCapacity(Staff $staff, PostgraduateResearchProject $project, bool $allowOverride = false): array
    {
        $accreditation = $this->getOrCreateAccreditation($staff);
        $accreditation->recalculateActiveCounts();
        $accreditation->refresh();

        $progType = strtoupper((string) ($project->student?->program?->type ?? 'MSC'));
        $isPhd = ($progType === 'PHD');

        if ($isPhd) {
            if (!$accreditation->is_phd_accredited && !$allowOverride) {
                throw ValidationException::withMessages([
                    'major_supervisor_id' => "Staff {$staff->staff_number} ({$staff->user?->name}) is not accredited for Ph.D doctoral supervision under SPGS guidelines.",
                ]);
            }

            if ($accreditation->active_phd_count >= $accreditation->max_phd_quota && !$allowOverride) {
                throw ValidationException::withMessages([
                    'major_supervisor_id' => "Supervisor {$staff->user?->name} has reached maximum NUC Ph.D quota ({$accreditation->active_phd_count}/{$accreditation->max_phd_quota} active candidates).",
                ]);
            }
        } else {
            if (!$accreditation->is_masters_accredited && !$allowOverride) {
                throw ValidationException::withMessages([
                    'major_supervisor_id' => "Staff {$staff->staff_number} ({$staff->user?->name}) is not accredited for Master's thesis supervision.",
                ]);
            }

            if ($accreditation->active_masters_count >= $accreditation->max_masters_quota && !$allowOverride) {
                throw ValidationException::withMessages([
                    'major_supervisor_id' => "Supervisor {$staff->user?->name} has reached maximum NUC Master's quota ({$accreditation->active_masters_count}/{$accreditation->max_masters_quota} active candidates).",
                ]);
            }
        }

        return [
            'valid' => true,
            'is_phd' => $isPhd,
            'current_load' => $isPhd ? $accreditation->active_phd_count : $accreditation->active_masters_count,
            'max_quota' => $isPhd ? $accreditation->max_phd_quota : $accreditation->max_masters_quota,
        ];
    }

    /**
     * Update an academic staff member's PG supervisory accreditation.
     */
    public function updateAccreditation(Staff $staff, array $data, ?User $accreditedBy = null): PostgraduateStaffAccreditation
    {
        $accreditation = $this->getOrCreateAccreditation($staff);

        $accreditation->update([
            'is_phd_accredited' => (bool) ($data['is_phd_accredited'] ?? $accreditation->is_phd_accredited),
            'is_masters_accredited' => (bool) ($data['is_masters_accredited'] ?? $accreditation->is_masters_accredited),
            'is_pgd_accredited' => (bool) ($data['is_pgd_accredited'] ?? $accreditation->is_pgd_accredited),
            'max_phd_quota' => isset($data['max_phd_quota']) ? (int) $data['max_phd_quota'] : $accreditation->max_phd_quota,
            'max_masters_quota' => isset($data['max_masters_quota']) ? (int) $data['max_masters_quota'] : $accreditation->max_masters_quota,
            'specialization_areas' => $data['specialization_areas'] ?? $accreditation->specialization_areas,
            'scopus_id' => $data['scopus_id'] ?? $accreditation->scopus_id,
            'orcid_id' => $data['orcid_id'] ?? $accreditation->orcid_id,
            'google_scholar_url' => $data['google_scholar_url'] ?? $accreditation->google_scholar_url,
            'accredited_by_user_id' => $accreditedBy?->id ?? $accreditation->accredited_by_user_id,
            'accredited_at' => now(),
        ]);

        $accreditation->recalculateActiveCounts();

        return $accreditation->fresh();
    }

    /**
     * Compute average review turnaround SLA for a supervisor in days.
     */
    public function computeSupervisorSlaDays(Staff $staff): float
    {
        $reviewedLogs = PostgraduateResearchLog::where('reviewed_by_staff_id', $staff->id)
            ->whereNotNull('reviewed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, reviewed_at)) as avg_days')
            ->first();

        $avg = $reviewedLogs?->avg_days;

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Register or update an external examiner in the master registry.
     */
    public function saveExternalExaminer(array $data): PostgraduateExternalExaminerRegistry
    {
        $currentSession = \App\Services\AcademicCacheService::getCurrentSession();

        return PostgraduateExternalExaminerRegistry::updateOrCreate(
            ['email' => strtolower(trim($data['email']))],
            [
                'title' => $data['title'] ?? 'Prof.',
                'full_name' => trim($data['full_name']),
                'institution' => trim($data['institution']),
                'department' => trim($data['department']),
                'phone' => $data['phone'] ?? null,
                'specialization' => trim($data['specialization']),
                'first_appointed_session_id' => $data['first_appointed_session_id'] ?? $currentSession?->id,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );
    }
}

