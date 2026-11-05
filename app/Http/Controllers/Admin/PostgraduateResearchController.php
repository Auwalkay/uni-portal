<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostgraduateCandidacyRequest;
use App\Models\PostgraduateExaminer;
use App\Models\PostgraduateProgressReport;
use App\Models\PostgraduateResearchLog;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Postgraduate\PostgraduateCandidacyService;
use App\Services\Postgraduate\PostgraduateResearchService;
use App\Services\Postgraduate\PostgraduateVivaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostgraduateResearchController extends Controller
{
    public function __construct(
        protected PostgraduateResearchService $researchService,
        protected PostgraduateCandidacyService $candidacyService,
        protected PostgraduateVivaService $vivaService
    ) {}

    public function index(Request $request)
    {
        $query = PostgraduateResearchProject::with([
            'student.user:id,name,email',
            'student.program.department.faculty:id,name',
            'majorSupervisor.user:id,name,email',
            'coSupervisor.user:id,name,email',
            'secondCoSupervisor.user:id,name,email',
            'milestones',
            'examiners',
            'course:id,code,title',
            'logs' => fn ($q) => $q->orderByDesc('entry_date')->limit(5),
            'progressReports' => fn ($q) => $q->latest()->limit(3),
        ]);

        if ($request->filled('pathway_type') && $request->pathway_type !== 'all') {
            $query->where('pathway_type', $request->pathway_type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('matriculation_number', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        // 5M-Scale Scalable Sample Initializers (Max 15 for modal init)
        $pgStudents = Student::with(['user:id,name,email', 'program:id,name,type,academic_stream'])
            ->where(function ($q) {
                $q->where('current_level', '>=', 700)
                    ->orWhereHas('program', fn ($pq) => $pq->where('academic_stream', 'PG'));
            })
            ->orderBy('matriculation_number')
            ->limit(15)
            ->get(['id', 'user_id', 'matriculation_number', 'program_id', 'current_level']);

        $supervisors = Staff::with(['user:id,name,email', 'department:id,name'])
            ->orderBy('staff_number')
            ->limit(15)
            ->get(['id', 'user_id', 'staff_number', 'designation', 'department_id']);

        $stats = [
            'total' => PostgraduateResearchProject::count(),
            'in_progress' => PostgraduateResearchProject::where('status', 'in_progress')->count(),
            'defense_scheduled' => PostgraduateResearchProject::where('status', 'defense_scheduled')->count(),
            'completed' => PostgraduateResearchProject::where('status', 'completed')->count(),
            'pending_petitions' => PostgraduateCandidacyRequest::where('status', 'pending')->count(),
            'pending_aprs' => PostgraduateProgressReport::where('spgs_status', 'submitted')->count(),
        ];

        // Pending Petitions & Reports for the SPGS Governance Desk
        $pendingPetitions = PostgraduateCandidacyRequest::with(['student.user:id,name', 'student.program:id,name'])
            ->where('status', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Postgraduate/Index', [
            'projects' => $projects,
            'pgStudents' => $pgStudents,
            'supervisors' => $supervisors,
            'stats' => $stats,
            'pendingPetitions' => $pendingPetitions,
            'pathwayTypes' => PostgraduateResearchProject::PATHWAY_TYPES,
            'filters' => $request->only(['search', 'pathway_type', 'status']),
        ]);
    }

    /**
     * Async Debounced Candidate Search (5M-Scale Safe).
     */
    public function searchCandidates(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $candidates = Student::with(['user:id,name,email', 'program:id,name'])
            ->where(function ($q) {
                $q->where('current_level', '>=', 700)
                    ->orWhereHas('program', fn ($pq) => $pq->where('academic_stream', 'PG'));
            })
            ->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->limit(15)
            ->get(['id', 'user_id', 'matriculation_number', 'program_id', 'current_level']);

        return response()->json($candidates);
    }

    /**
     * Async Debounced Supervisor Search (5M-Scale Safe).
     */
    public function searchSupervisors(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $supervisors = Staff::with(['user:id,name,email', 'department:id,name'])
            ->where(function ($q) use ($search) {
                $q->where('staff_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->limit(15)
            ->get(['id', 'user_id', 'staff_number', 'designation', 'department_id']);

        return response()->json($supervisors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'title' => 'required|string|max:500',
            'abstract' => 'nullable|string|max:5000',
            'research_area' => 'nullable|string|max:255',
            'pathway_type' => 'nullable|string|in:pgd_project,capstone_consulting,academic_dissertation',
            'major_supervisor_id' => 'nullable|exists:staff,id',
            'co_supervisor_id' => 'nullable|exists:staff,id',
            'co_supervisor_2_id' => 'nullable|exists:staff,id',
            'thesis_document' => 'nullable|file|mimes:pdf|max:30720',
        ]);

        if ($request->hasFile('thesis_document')) {
            $path = $request->file('thesis_document')->store('postgraduate/theses', 'public');
            $validated['thesis_document_path'] = $path;
        }

        $student = Student::with('program')->findOrFail($validated['student_id']);
        $project = $this->researchService->createOrUpdateProject($student, $validated);

        if (!empty($validated['major_supervisor_id']) || !empty($validated['co_supervisor_id'])) {
            $this->researchService->allocateSupervisors($project, $validated);
        }

        return back()->with('success', 'Postgraduate research/capstone project initialized successfully.');
    }

    public function allocateSupervisors(Request $request, PostgraduateResearchProject $project)
    {
        $validated = $request->validate([
            'major_supervisor_id' => 'required|exists:staff,id',
            'co_supervisor_id' => 'nullable|exists:staff,id',
            'co_supervisor_2_id' => 'nullable|exists:staff,id',
            'external_examiner_name' => 'nullable|string|max:200',
            'external_examiner_institution' => 'nullable|string|max:250',
            'external_examiner_email' => 'nullable|email|max:200',
        ]);

        $this->researchService->allocateSupervisors($project, $validated);

        return back()->with('success', 'Supervisors and external examiner updated successfully.');
    }

    public function reviewMilestone(Request $request, PostgraduateResearchMilestone $milestone)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:revision_requested,supervisor_approved,spgs_cleared',
            'supervisor_remarks' => 'nullable|string|max:3000',
            'spgs_remarks' => 'nullable|string|max:3000',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        $this->researchService->reviewMilestone($milestone, $validated, $request->user()?->id);

        return back()->with('success', 'Milestone assessment recorded.');
    }

    public function scheduleDefense(Request $request, PostgraduateResearchProject $project)
    {
        $validated = $request->validate([
            'defense_date' => 'required|date',
            'defense_venue' => 'required|string|max:255',
            'defense_panel_notes' => 'nullable|string|max:3000',
            'external_examiner_name' => 'nullable|string|max:200',
            'external_examiner_institution' => 'nullable|string|max:250',
            'external_examiner_email' => 'nullable|email|max:200',
        ]);

        $this->researchService->scheduleVivaDefense($project, $validated);

        return back()->with('success', 'Viva Voce / Panel Defense scheduled.');
    }

    public function recordVerdict(Request $request, PostgraduateResearchProject $project)
    {
        $validated = $request->validate([
            'final_score' => 'required|numeric|min:0|max:100',
            'defense_status' => 'required|string|in:passed,passed_minor_corrections,passed_major_corrections,referred,failed',
            'defense_panel_notes' => 'nullable|string|max:5000',
            'repository_url' => 'nullable|string|max:500',
        ]);

        $this->researchService->recordVivaDefenseVerdict(
            $project,
            (float) $validated['final_score'],
            $validated['defense_status'],
            $validated['defense_panel_notes'] ?? null,
            $validated['repository_url'] ?? null
        );

        return back()->with('success', 'Viva Voce verdict recorded and synced with SPGS broadsheet.');
    }

    public function addExaminer(Request $request, PostgraduateResearchProject $project)
    {
        $validated = $request->validate([
            'examiner_type' => 'required|in:internal_examiner,external_examiner,spgs_representative',
            'title' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'institution_or_department' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'specialization' => 'nullable|string|max:255',
            'draft_dispatched_at' => 'nullable|date',
            'dispatch_tracking_ref' => 'nullable|string|max:255',
        ]);

        $this->vivaService->addExaminer($project, $validated);

        return back()->with('success', 'Examiner nominated and recorded successfully.');
    }

    public function recordExaminerReport(Request $request, PostgraduateExaminer $examiner)
    {
        $validated = $request->validate([
            'report_received_at' => 'nullable|date',
            'pre_viva_recommendation' => 'required|in:proceed_to_viva,minor_revisions_first,unacceptable',
            'examiner_comments' => 'nullable|string|max:5000',
            'report_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $path = $examiner->report_file_path;
        if ($request->hasFile('report_file')) {
            $path = $request->file('report_file')->store('postgraduate/examiner_reports', 'public');
        }

        $this->vivaService->recordExaminerReport($examiner, [
            'report_received_at' => $validated['report_received_at'] ?? now()->toDateString(),
            'pre_viva_recommendation' => $validated['pre_viva_recommendation'],
            'examiner_comments' => $validated['examiner_comments'] ?? null,
            'report_file_path' => $path,
        ]);

        return back()->with('success', 'Examiner evaluation report saved.');
    }

    public function processCandidacyRequest(Request $request, PostgraduateCandidacyRequest $candidacyRequest)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:2000',
        ]);

        $this->candidacyService->processCandidacyRequest(
            $candidacyRequest,
            $request->user(),
            $validated['action'],
            $validated['reason'] ?? null
        );

        return back()->with('success', 'Candidacy petition decision processed.');
    }

    public function reviewProgressReport(Request $request, PostgraduateProgressReport $progressReport)
    {
        $validated = $request->validate([
            'action' => 'required|in:certify,needs_work',
        ]);

        $staff = Staff::where('user_id', $request->user()->id)->first();
        if ($staff) {
            $this->candidacyService->certifyProgressReportBySpgs($progressReport, $staff);
        } else {
            $progressReport->update(['spgs_status' => 'spgs_certified']);
        }

        return back()->with('success', 'Annual Progress Report (APR) certified by SPGS.');
    }
}
