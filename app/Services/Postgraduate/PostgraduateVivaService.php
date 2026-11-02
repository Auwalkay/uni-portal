<?php

namespace App\Services\Postgraduate;

use App\Models\PostgraduateExaminer;
use App\Models\PostgraduateResearchProject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostgraduateVivaService
{
    /**
     * Nominate or add an examiner to a postgraduate project.
     */
    public function addExaminer(PostgraduateResearchProject $project, array $data): PostgraduateExaminer
    {
        return PostgraduateExaminer::create([
            'project_id' => $project->id,
            'examiner_type' => $data['examiner_type'], // internal_examiner, external_examiner, spgs_representative
            'title' => $data['title'] ?? 'Prof.',
            'full_name' => $data['full_name'],
            'institution_or_department' => $data['institution_or_department'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'specialization' => $data['specialization'] ?? null,
            'draft_dispatched_at' => $data['draft_dispatched_at'] ?? null,
            'dispatch_tracking_ref' => $data['dispatch_tracking_ref'] ?? null,
        ]);
    }

    /**
     * Record examiner's pre-viva evaluation report.
     */
    public function recordExaminerReport(PostgraduateExaminer $examiner, array $data): PostgraduateExaminer
    {
        $examiner->update([
            'report_received_at' => $data['report_received_at'] ?? now(),
            'pre_viva_recommendation' => $data['pre_viva_recommendation'], // proceed_to_viva, minor_revisions_first, unacceptable
            'examiner_comments' => $data['examiner_comments'] ?? null,
            'report_file_path' => $data['report_file_path'] ?? $examiner->report_file_path,
        ]);

        return $examiner;
    }

    /**
     * Schedule Oral Defense / Viva Voce.
     */
    public function scheduleOralDefense(PostgraduateResearchProject $project, array $data): PostgraduateResearchProject
    {
        $project->update([
            'defense_date' => $data['defense_date'],
            'defense_venue' => $data['defense_venue'],
            'defense_status' => 'scheduled',
            'status' => 'defense_scheduled',
        ]);

        return $project;
    }

    /**
     * Record Viva Voce Broadsheet Scoring & Panel Verdict.
     */
    public function recordDefenseVerdict(PostgraduateResearchProject $project, array $data): PostgraduateResearchProject
    {
        return DB::transaction(function () use ($project, $data) {
            $score = (float) $data['final_score'];
            $grade = $data['final_grade'] ?? $this->computeGradeFromScore($score);
            $verdict = $data['defense_verdict']; // passed_clean, minor_corrections, major_corrections, failed

            $defenseStatus = match ($verdict) {
                'passed_clean' => 'passed',
                'minor_corrections' => 'corrections_pending',
                'major_corrections' => 'corrections_pending',
                'failed' => 'failed',
                default => 'passed',
            };

            $status = ($verdict === 'passed_clean') ? 'completed' : 'defense_scheduled';

            $project->update([
                'final_score' => $score,
                'final_grade' => $grade,
                'defense_status' => $defenseStatus,
                'defense_report' => $data['defense_report'] ?? null,
                'status' => $status,
                'approved_at' => ($verdict === 'passed_clean') ? now() : null,
            ]);

            return $project;
        });
    }

    /**
     * Certify post-defense corrections signed off by Examiners / HOD.
     */
    public function certifyCorrections(PostgraduateResearchProject $project): PostgraduateResearchProject
    {
        $project->update([
            'defense_status' => 'passed',
            'status' => 'completed',
            'approved_at' => now(),
        ]);

        return $project;
    }

    protected function computeGradeFromScore(float $score): string
    {
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        return 'F';
    }
}

