<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PostgraduateCandidacyRequest;
use App\Models\PostgraduateProgressReport;
use App\Models\PostgraduatePublication;
use App\Models\PostgraduateResearchLog;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Services\Finance\FeeService;
use App\Services\Postgraduate\PostgraduateCandidacyService;
use App\Services\Postgraduate\PostgraduateResearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostgraduateResearchController extends Controller
{
    public function __construct(
        protected PostgraduateResearchService $researchService,
        protected PostgraduateCandidacyService $candidacyService,
        protected FeeService $feeService
    ) {}

    public function index(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            return redirect()->route('student.dashboard');
        }

        $student->load(['program.department.faculty', 'postgraduateProfile']);

        $project = PostgraduateResearchProject::with([
            'majorSupervisor.user:id,name,email',
            'coSupervisor.user:id,name,email',
            'secondCoSupervisor.user:id,name,email',
            'milestones',
            'examiners',
            'course:id,code,title,units',
        ])
            ->where('student_id', $student->id)
            ->first();

        // 5M-Scale Paginated Data Collections
        $logs = $project
            ? PostgraduateResearchLog::with('supervisor.user:id,name')
                ->where('project_id', $project->id)
                ->orderByDesc('entry_date')
                ->paginate(10, ['*'], 'logs_page')
                ->withQueryString()
            : null;

        $progressReports = $project
            ? PostgraduateProgressReport::with(['session:id,name', 'dpgcApprover.user:id,name'])
                ->where('project_id', $project->id)
                ->latest()
                ->paginate(10, ['*'], 'reports_page')
                ->withQueryString()
            : null;

        $candidacyRequests = PostgraduateCandidacyRequest::where('student_id', $student->id)
            ->latest()
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        $publications = $project
            ? PostgraduatePublication::where('project_id', $project->id)
                ->latest()
                ->paginate(10, ['*'], 'pubs_page')
                ->withQueryString()
            : null;

        $session = $student->resolveCurrentSession();
        $milestoneFees = ($session && $student->isPostgraduate())
            ? $this->feeService->getAvailableMilestoneFees($student, $session)->values()
            : collect();

        $schoolFeeInvoice = $session
            ? Invoice::where('user_id', $request->user()->id)
                ->where('type', 'school_fee')
                ->where('session_id', $session->id)
                ->first()
            : null;
        $schoolFeeStatus = $schoolFeeInvoice?->status ?? 'unpaid';
        $hasPaidFees = in_array($schoolFeeStatus, ['paid', 'partial'], true);

        return Inertia::render('Student/Postgraduate/Research', [
            'student' => $student,
            'project' => $project,
            'logs' => $logs,
            'progressReports' => $progressReports,
            'candidacyRequests' => $candidacyRequests,
            'publications' => $publications,
            'defaultPathway' => $this->researchService->resolvePathwayTypeForStudent($student),
            'pathwayTypes' => PostgraduateResearchProject::PATHWAY_TYPES,
            'milestoneFees' => $milestoneFees,
            'schoolFeeStatus' => $schoolFeeStatus,
            'hasPaidFees' => $hasPaidFees,
        ]);
    }

    public function storeTopic(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            abort(403, 'Student profile required.');
        }

        $session = $student->resolveCurrentSession();
        $schoolFeeInvoice = $session
            ? Invoice::where('user_id', $request->user()->id)
                ->where('type', 'school_fee')
                ->where('session_id', $session->id)
                ->first()
            : null;
        $hasPaidFees = in_array($schoolFeeInvoice?->status, ['paid', 'partial'], true);

        if (!$hasPaidFees) {
            return back()->with('error', 'Thesis submission locked: Please complete your academic school fees payment before registering your research topic.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'nullable|string|max:5000',
            'research_area' => 'nullable|string|max:255',
            'thesis_document' => 'nullable|file|mimes:pdf|max:30720',
        ]);

        if ($request->hasFile('thesis_document')) {
            $path = $request->file('thesis_document')->store('postgraduate/theses', 'public');
            $validated['thesis_document_path'] = $path;
        }

        $this->researchService->createOrUpdateProject($student, $validated);

        return back()->with('success', 'Research / Capstone topic registered and milestones initialized.');
    }

    public function submitMilestone(Request $request, PostgraduateResearchMilestone $milestone)
    {
        $student = $request->user()->student;
        if (!$student || $milestone->project?->student_id !== $student->id) {
            abort(403, 'Unauthorized milestone submission.');
        }

        $validated = $request->validate([
            'submission_notes' => 'nullable|string|max:3000',
            'plagiarism_similarity_index' => 'nullable|numeric|min:0|max:100',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $path = $milestone->document_path;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('postgraduate/milestones', 'public');
        }

        $this->researchService->submitMilestone($milestone, [
            'submission_notes' => $validated['submission_notes'] ?? null,
            'plagiarism_similarity_index' => $validated['plagiarism_similarity_index'] ?? null,
            'document_path' => $path,
        ]);

        return back()->with('success', 'Milestone submitted for supervisor / SPGS review.');
    }

    public function storeLog(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            abort(403, 'Student profile required.');
        }

        $validated = $request->validate([
            'entry_date' => 'required|date|before_or_equal:today',
            'activity_summary' => 'required|string|max:255',
            'details_of_work_done' => 'required|string|max:5000',
            'challenges_and_next_steps' => 'nullable|string|max:3000',
            'document' => 'nullable|file|mimes:pdf,doc,docx,zip|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('postgraduate/logs', 'public');
        }

        $this->researchService->recordLogEntry($student, [
            'entry_date' => $validated['entry_date'],
            'activity_summary' => $validated['activity_summary'],
            'details_of_work_done' => $validated['details_of_work_done'],
            'challenges_and_next_steps' => $validated['challenges_and_next_steps'] ?? null,
            'document_attachment_path' => $path,
        ]);

        return back()->with('success', 'Digital research logbook entry logged successfully.');
    }

    public function storeProgressReport(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            abort(403, 'Student profile required.');
        }

        $validated = $request->validate([
            'reporting_period' => 'required|in:first_semester,second_semester,annual',
            'summary_of_progress' => 'required|string|max:5000',
            'chapters_completed' => 'required|string|max:2000',
            'fieldwork_or_lab_status' => 'required|string|max:2000',
            'publications_or_conferences' => 'nullable|string|max:2000',
            'completion_percentage' => 'required|integer|min:0|max:100',
        ]);

        $this->candidacyService->submitProgressReport($student, $validated);

        return back()->with('success', 'Annual Progress Report (APR) submitted for supervisory evaluation.');
    }

    public function storeCandidacyRequest(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            abort(403, 'Student profile required.');
        }

        $validated = $request->validate([
            'request_type' => 'required|in:extension_of_time,deferment_of_candidacy,change_of_title,change_of_supervisor,upgrade_mphil_to_phd',
            'proposed_title' => 'nullable|required_if:request_type,change_of_title|string|max:500',
            'extension_months' => 'nullable|required_if:request_type,extension_of_time|integer|min:1|max:24',
            'justification_and_timeline' => 'required|string|max:4000',
            'supporting_document' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('supporting_document')) {
            $path = $request->file('supporting_document')->store('postgraduate/petitions', 'public');
        }

        $this->candidacyService->submitCandidacyRequest($student, [
            'request_type' => $validated['request_type'],
            'proposed_title' => $validated['proposed_title'] ?? null,
            'extension_months' => $validated['extension_months'] ?? null,
            'justification_and_timeline' => $validated['justification_and_timeline'],
            'supporting_document_path' => $path,
        ]);

        return back()->with('success', 'Candidacy petition submitted to the School of Postgraduate Studies Board.');
    }

    public function storePublication(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) {
            abort(403, 'Student profile required.');
        }

        $validated = $request->validate([
            'article_title' => 'required|string|max:500',
            'journal_name' => 'required|string|max:255',
            'issn_isbn' => 'nullable|string|max:50',
            'doi_or_url' => 'nullable|string|max:255',
            'indexing_agency' => 'nullable|string|max:100',
            'publication_status' => 'required|in:submitted,accepted_in_press,published',
            'evidence_document' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('evidence_document')) {
            $path = $request->file('evidence_document')->store('postgraduate/publications', 'public');
        }

        $this->candidacyService->recordPublication($student, [
            'article_title' => $validated['article_title'],
            'journal_name' => $validated['journal_name'],
            'issn_isbn' => $validated['issn_isbn'] ?? null,
            'doi_or_url' => $validated['doi_or_url'] ?? null,
            'indexing_agency' => $validated['indexing_agency'] ?? 'NUC-Recognized',
            'publication_status' => $validated['publication_status'],
            'evidence_document_path' => $path,
        ]);

        return back()->with('success', 'Publication evidence uploaded for SPGS verification.');
    }
}
