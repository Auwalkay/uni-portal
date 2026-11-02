<?php

namespace App\Services\Postgraduate;

use App\Models\CourseRegistration;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Admissions\PostgraduateAdmissionService;
use App\Services\GradingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostgraduateResearchService
{
    public const MAX_PLAGIARISM_SIMILARITY = 20.0;

    public function __construct(
        protected GradingService $gradingService,
        protected PostgraduateAdmissionService $admissionService,
        protected PostgraduateStaffGovernanceService $staffGovernanceService
    ) {}

    public function resolvePathwayTypeForStudent(Student $student): string
    {
        $programme = $student->program;
        if (!$programme) {
            return 'academic_dissertation';
        }

        $tier = $this->admissionService->resolveProgrammeTier($programme);

        return match ($tier) {
            'PGD' => 'pgd_project',
            'PROFESSIONAL_MASTERS' => 'capstone_consulting',
            default => 'academic_dissertation',
        };
    }

    public function createOrUpdateProject(Student $student, array $data): PostgraduateResearchProject
    {
        return DB::transaction(function () use ($student, $data) {
            $session = $student->resolveCurrentSession() ?? Session::current();
            $pathwayType = $data['pathway_type'] ?? $this->resolvePathwayTypeForStudent($student);

            $courseRegistrationId = $data['course_registration_id'] ?? null;
            $courseId = $data['course_id'] ?? null;

            if (!$courseRegistrationId) {
                $matchingReg = CourseRegistration::with('course')
                    ->where('student_id', $student->id)
                    ->whereHas('course', function ($q) {
                        $q->whereIn('course_type', ['capstone_project', 'academic_dissertation', 'practicum_internship']);
                    })
                    ->latest()
                    ->first();

                if ($matchingReg) {
                    $courseRegistrationId = $matchingReg->id;
                    $courseId = $courseId ?? $matchingReg->course_id;
                }
            }

            $updatePayload = [
                'session_id' => $data['session_id'] ?? $session?->id,
                'course_id' => $courseId,
                'course_registration_id' => $courseRegistrationId,
                'pathway_type' => $pathwayType,
                'title' => $data['title'],
                'abstract' => $data['abstract'] ?? null,
                'research_area' => $data['research_area'] ?? null,
                'status' => $data['status'] ?? 'topic_proposed',
            ];

            if (!empty($data['thesis_document_path'])) {
                $updatePayload['thesis_document_path'] = $data['thesis_document_path'];
                $updatePayload['thesis_submitted_at'] = now();
            }

            $project = PostgraduateResearchProject::updateOrCreate(
                ['student_id' => $student->id],
                $updatePayload
            );

            $project->initializeDefaultMilestones();

            return $project->load(['milestones', 'majorSupervisor', 'coSupervisor', 'secondCoSupervisor']);
        });
    }

    public function allocateSupervisors(PostgraduateResearchProject $project, array $data): PostgraduateResearchProject
    {
        $majorId = $data['major_supervisor_id'] ?? null;
        $coId = $data['co_supervisor_id'] ?? null;
        $co2Id = $data['co_supervisor_2_id'] ?? null;

        if ($majorId && $coId && $majorId === $coId) {
            throw ValidationException::withMessages([
                'co_supervisor_id' => 'Co-Supervisor I must be different from the Major Supervisor.',
            ]);
        }

        if ($majorId && $co2Id && $majorId === $co2Id) {
            throw ValidationException::withMessages([
                'co_supervisor_2_id' => 'Co-Supervisor II must be different from the Major Supervisor.',
            ]);
        }

        if ($majorId) {
            $majorStaff = Staff::find($majorId);
            if ($majorStaff) {
                $allowOverride = !empty($data['allow_quota_override']);
                $this->staffGovernanceService->validateSupervisorCapacity($majorStaff, $project, $allowOverride);
            }
        }

        $nextStatus = $project->status === 'topic_proposed' ? 'in_progress' : $project->status;

        $project->update([
            'major_supervisor_id' => $majorId,
            'co_supervisor_id' => $coId,
            'co_supervisor_2_id' => $co2Id,
            'external_examiner_name' => $data['external_examiner_name'] ?? $project->external_examiner_name,
            'external_examiner_institution' => $data['external_examiner_institution'] ?? $project->external_examiner_institution,
            'external_examiner_email' => $data['external_examiner_email'] ?? $project->external_examiner_email,
            'status' => $nextStatus,
        ]);

        if ($majorId && isset($majorStaff)) {
            $majorStaff->postgraduateAccreditation?->recalculateActiveCounts();
        }

        return $project->fresh(['majorSupervisor', 'coSupervisor', 'secondCoSupervisor']);
    }

    public function submitMilestone(PostgraduateResearchMilestone $milestone, array $data): PostgraduateResearchMilestone
    {
        $similarity = isset($data['plagiarism_similarity_index']) && $data['plagiarism_similarity_index'] !== ''
            ? (float) $data['plagiarism_similarity_index']
            : null;

        if (
            $similarity !== null &&
            in_array($milestone->stage, ['pre_defense_draft', 'capstone_draft', 'draft_report'], true) &&
            $similarity > self::MAX_PLAGIARISM_SIMILARITY
        ) {
            throw ValidationException::withMessages([
                'plagiarism_similarity_index' => 'Plagiarism similarity index (' . $similarity . '%) exceeds the maximum SPGS benchmark of ' . self::MAX_PLAGIARISM_SIMILARITY . '%.',
            ]);
        }

        $milestone->update([
            'submission_notes' => $data['submission_notes'] ?? $milestone->submission_notes,
            'document_path' => $data['document_path'] ?? $milestone->document_path,
            'plagiarism_similarity_index' => $similarity ?? $milestone->plagiarism_similarity_index,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return $milestone->fresh();
    }

    /**
     * Record a new candidate logbook entry.
     */
    public function recordLogEntry(Student $student, array $data): \App\Models\PostgraduateResearchLog
    {
        $project = PostgraduateResearchProject::where('student_id', $student->id)->firstOrFail();

        return \App\Models\PostgraduateResearchLog::create([
            'project_id' => $project->id,
            'student_id' => $student->id,
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
            'activity_summary' => $data['activity_summary'],
            'details_of_work_done' => $data['details_of_work_done'],
            'challenges_and_next_steps' => $data['challenges_and_next_steps'] ?? null,
            'document_attachment_path' => $data['document_attachment_path'] ?? null,
            'supervisor_status' => 'pending',
        ]);
    }

    /**
     * Supervisor reviews a candidate logbook entry.
     */
    public function reviewLogEntry(\App\Models\PostgraduateResearchLog $log, ?Staff $supervisor, array $data): \App\Models\PostgraduateResearchLog
    {
        $log->update([
            'reviewed_by_staff_id' => $supervisor?->id,
            'reviewed_at' => now(),
            'supervisor_status' => $data['supervisor_status'], // reviewed, needs_revision
            'supervisor_feedback' => $data['supervisor_feedback'] ?? null,
        ]);

        return $log->fresh();
    }

    public function reviewMilestone(
        PostgraduateResearchMilestone $milestone,
        array $data,
        ?string $reviewerUserId = null
    ): PostgraduateResearchMilestone {
        $updateData = [
            'status' => $data['status'], // revision_requested, supervisor_approved, spgs_cleared
            'supervisor_remarks' => $data['supervisor_remarks'] ?? $milestone->supervisor_remarks,
            'spgs_remarks' => $data['spgs_remarks'] ?? $milestone->spgs_remarks,
            'score' => isset($data['score']) && $data['score'] !== '' ? (float) $data['score'] : $milestone->score,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerUserId,
        ];

        if (isset($data['plagiarism_similarity_index']) && $data['plagiarism_similarity_index'] !== '') {
            $updateData['plagiarism_similarity_index'] = (float) $data['plagiarism_similarity_index'];
        }

        if (!empty($data['document_path'])) {
            $updateData['document_path'] = $data['document_path'];
            $updateData['submitted_at'] = now();
        }

        $milestone->update($updateData);

        return $milestone->fresh();
    }

    public function scheduleVivaDefense(PostgraduateResearchProject $project, array $data): PostgraduateResearchProject
    {
        $project->update([
            'defense_date' => $data['defense_date'],
            'defense_venue' => $data['defense_venue'],
            'defense_panel_notes' => $data['defense_panel_notes'] ?? $project->defense_panel_notes,
            'external_examiner_name' => $data['external_examiner_name'] ?? $project->external_examiner_name,
            'external_examiner_institution' => $data['external_examiner_institution'] ?? $project->external_examiner_institution,
            'external_examiner_email' => $data['external_examiner_email'] ?? $project->external_examiner_email,
            'defense_status' => 'scheduled',
            'status' => 'defense_scheduled',
        ]);

        return $project->fresh();
    }

    public function recordVivaDefenseVerdict(
        PostgraduateResearchProject $project,
        float $finalScore,
        string $defenseVerdict,
        ?string $panelNotes = null,
        ?string $repositoryUrl = null
    ): PostgraduateResearchProject {
        return DB::transaction(function () use ($project, $finalScore, $defenseVerdict, $panelNotes, $repositoryUrl) {
            $grading = $this->gradingService->calculateForPostgraduate($finalScore);
            $gradePoint = (float) ($grading['point'] ?? $grading['gp'] ?? 0.0);

            $isPassed = in_array($defenseVerdict, ['passed', 'passed_minor_corrections'], true) && $finalScore >= 50.0;
            $projectStatus = $isPassed
                ? 'completed'
                : ($defenseVerdict === 'passed_major_corrections' ? 'corrections_pending' : 'in_progress');

            $project->update([
                'final_score' => $finalScore,
                'final_grade' => $grading['grade'],
                'final_grade_point' => $gradePoint,
                'defense_status' => $defenseVerdict,
                'defense_panel_notes' => $panelNotes ?? $project->defense_panel_notes,
                'repository_url' => $repositoryUrl ?? $project->repository_url,
                'status' => $projectStatus,
                'approved_at' => $isPassed ? now() : null,
            ]);

            // Update defense milestone status & score if present
            $defenseMilestone = $project->milestones()
                ->whereIn('stage', ['viva_voce_defense', 'panel_presentation', 'internal_review'])
                ->first();

            if ($defenseMilestone) {
                $defenseMilestone->update([
                    'status' => $isPassed ? 'spgs_cleared' : 'revision_requested',
                    'score' => $finalScore,
                    'spgs_remarks' => $panelNotes,
                    'reviewed_at' => now(),
                ]);
            }

            // Sync with CourseRegistration for SPGS Broadsheet & Student Transcript
            $registration = $project->courseRegistration;
            if (!$registration) {
                $registration = CourseRegistration::where('student_id', $project->student_id)
                    ->whereHas('course', function ($q) {
                        $q->whereIn('course_type', ['capstone_project', 'academic_dissertation', 'practicum_internship']);
                    })
                    ->latest()
                    ->first();
            }

            if ($registration) {
                $registration->update([
                    'ca_score' => 0,
                    'exam_score' => $finalScore,
                    'score' => $finalScore,
                    'grade' => $grading['grade'],
                    'grade_point' => $gradePoint,
                    'is_absent' => false,
                ]);

                if (!$project->course_registration_id) {
                    $project->update([
                        'course_registration_id' => $registration->id,
                        'course_id' => $registration->course_id,
                    ]);
                }
            }

            return $project->fresh(['milestones', 'courseRegistration']);
        });
    }

    /**
     * Calculate the cumulative GPA (CGPA) for a Postgraduate student across all graded course registrations.
     */
    public function calculateStudentCgpa(Student $student): float
    {
        $registrations = CourseRegistration::with('course')
            ->where('student_id', $student->id)
            ->whereNotNull('grade_point')
            ->get();

        if ($registrations->isEmpty()) {
            return 0.0;
        }

        $totalWeightedPoints = 0.0;
        $totalUnits = 0;

        foreach ($registrations as $reg) {
            $units = max((int) ($reg->course?->units ?? 0), 1);
            $totalWeightedPoints += ((float) $reg->grade_point) * $units;
            $totalUnits += $units;
        }

        return $totalUnits > 0 ? round($totalWeightedPoints / $totalUnits, 2) : 0.0;
    }
}
