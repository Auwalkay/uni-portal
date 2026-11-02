<?php

namespace App\Services\Postgraduate;

use App\Models\PostgraduateCandidacyRequest;
use App\Models\PostgraduateProgressReport;
use App\Models\PostgraduatePublication;
use App\Models\PostgraduateResearchProject;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostgraduateCandidacyService
{
    /**
     * Submit an Annual / Bi-annual Progress Report (APR) for a postgraduate candidate.
     */
    public function submitProgressReport(Student $student, array $data): PostgraduateProgressReport
    {
        $project = PostgraduateResearchProject::where('student_id', $student->id)->firstOrFail();
        $session = $student->resolveCurrentSession() ?? Session::current();

        if (!$session) {
            throw ValidationException::withMessages(['session' => 'Active academic session required for progress reporting.']);
        }

        return DB::transaction(function () use ($project, $student, $session, $data) {
            return PostgraduateProgressReport::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'student_id' => $student->id,
                    'session_id' => $session->id,
                    'reporting_period' => $data['reporting_period'] ?? 'annual',
                ],
                [
                    'summary_of_progress' => $data['summary_of_progress'],
                    'chapters_completed' => $data['chapters_completed'],
                    'fieldwork_or_lab_status' => $data['fieldwork_or_lab_status'],
                    'publications_or_conferences' => $data['publications_or_conferences'] ?? null,
                    'completion_percentage' => min(100, max(0, (int) ($data['completion_percentage'] ?? 0))),
                    'spgs_status' => 'submitted',
                ]
            );
        });
    }

    /**
     * Supervisor reviews and grades an Annual Progress Report.
     */
    public function reviewProgressReportBySupervisor(PostgraduateProgressReport $report, ?Staff $supervisor, array $data): PostgraduateProgressReport
    {
        $report->update([
            'supervisor_rating' => $data['supervisor_rating'], // excellent, satisfactory, marginal, unsatisfactory
            'supervisor_remarks' => $data['supervisor_remarks'] ?? null,
            'supervisor_signed_at' => now(),
            'spgs_status' => 'supervisor_approved',
        ]);

        return $report;
    }

    /**
     * DPGC / SPGS Board certifies an Annual Progress Report.
     */
    public function certifyProgressReportBySpgs(PostgraduateProgressReport $report, Staff $approver): PostgraduateProgressReport
    {
        $report->update([
            'dpgc_approved_by_staff_id' => $approver->id,
            'dpgc_approved_at' => now(),
            'spgs_status' => 'spgs_certified',
        ]);

        return $report;
    }

    /**
     * Submit a candidacy petition (Sundry form: extension, deferment, title change).
     */
    public function submitCandidacyRequest(Student $student, array $data): PostgraduateCandidacyRequest
    {
        $project = PostgraduateResearchProject::where('student_id', $student->id)->first();

        return PostgraduateCandidacyRequest::create([
            'student_id' => $student->id,
            'project_id' => $project?->id,
            'request_type' => $data['request_type'],
            'proposed_title' => $data['proposed_title'] ?? null,
            'extension_months' => isset($data['extension_months']) ? (int) $data['extension_months'] : null,
            'justification_and_timeline' => $data['justification_and_timeline'],
            'supporting_document_path' => $data['supporting_document_path'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Process / Approve a candidacy petition by SPGS Authority.
     */
    public function processCandidacyRequest(PostgraduateCandidacyRequest $request, User $reviewer, string $action, ?string $reason = null): PostgraduateCandidacyRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $action, $reason) {
            if ($action === 'approve') {
                $request->update([
                    'status' => 'spgs_approved',
                    'approved_by_user_id' => $reviewer->id,
                    'approved_at' => now(),
                ]);

                // If title change was approved, update the research project
                if ($request->request_type === 'change_of_title' && $request->proposed_title && $request->project) {
                    $request->project->update([
                        'title' => $request->proposed_title,
                    ]);
                }
            } else {
                $request->update([
                    'status' => 'rejected',
                    'rejection_reason' => $reason ?? 'Request did not meet SPGS Board criteria.',
                    'approved_by_user_id' => $reviewer->id,
                    'approved_at' => now(),
                ]);
            }

            return $request;
        });
    }

    /**
     * Register a peer-reviewed publication or conference paper.
     */
    public function recordPublication(Student $student, array $data): PostgraduatePublication
    {
        $project = PostgraduateResearchProject::where('student_id', $student->id)->firstOrFail();

        return PostgraduatePublication::create([
            'student_id' => $student->id,
            'project_id' => $project->id,
            'article_title' => $data['article_title'],
            'journal_name' => $data['journal_name'],
            'issn_isbn' => $data['issn_isbn'] ?? null,
            'doi_or_url' => $data['doi_or_url'] ?? null,
            'indexing_agency' => $data['indexing_agency'] ?? 'NUC-Recognized',
            'publication_status' => $data['publication_status'] ?? 'published',
            'evidence_document_path' => $data['evidence_document_path'] ?? null,
            'is_spgs_verified' => false,
        ]);
    }

    /**
     * SPGS verifies publication authenticity.
     */
    public function verifyPublication(PostgraduatePublication $publication): PostgraduatePublication
    {
        $publication->update(['is_spgs_verified' => true]);
        return $publication;
    }
}

