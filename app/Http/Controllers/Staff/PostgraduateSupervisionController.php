<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PostgraduateProgressReport;
use App\Models\PostgraduateResearchLog;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Services\Postgraduate\PostgraduateCandidacyService;
use App\Services\Postgraduate\PostgraduateResearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostgraduateSupervisionController extends Controller
{
    public function __construct(
        protected PostgraduateResearchService $researchService,
        protected PostgraduateCandidacyService $candidacyService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $staff = $user->staff;
        $isAdmin = $user->hasRole(['super_admin', 'admin', 'dean_spgs']) || $user->can('manage_postgraduate_school');

        if (!$staff && !$isAdmin) {
            abort(403, 'Academic staff profile required for postgraduate supervision.');
        }

        // Fetch candidate projects (filtered by supervisor if staff, or institutional view if admin)
        $projectsQuery = PostgraduateResearchProject::with([
            'student.user:id,name,email',
            'student.program.department:id,name',
            'majorSupervisor.user:id,name',
            'coSupervisor.user:id,name',
            'milestones',
            'logs' => fn ($q) => $q->orderByDesc('entry_date')->limit(5),
            'progressReports' => fn ($q) => $q->latest()->limit(3),
        ]);

        if ($staff) {
            $projectsQuery->where(function ($q) use ($staff) {
                $q->where('major_supervisor_id', $staff->id)
                    ->orWhere('co_supervisor_id', $staff->id)
                    ->orWhere('co_supervisor_2_id', $staff->id);
            });
        }

        $projects = $projectsQuery->latest()->paginate(12);

        // Fetch unreviewed logbook entries
        $pendingLogsQuery = PostgraduateResearchLog::with(['student.user:id,name', 'project:id,title'])
            ->where('supervisor_status', 'pending');

        if ($staff) {
            $pendingLogsQuery->whereIn('project_id', function ($sub) use ($staff) {
                $sub->select('id')
                    ->from('postgraduate_research_projects')
                    ->where('major_supervisor_id', $staff->id)
                    ->orWhere('co_supervisor_id', $staff->id)
                    ->orWhere('co_supervisor_2_id', $staff->id);
            });
        }

        $pendingLogs = $pendingLogsQuery->orderBy('entry_date')->limit(10)->get();

        $stats = [
            'total_candidates' => $staff
                ? PostgraduateResearchProject::where('major_supervisor_id', $staff->id)->count()
                : PostgraduateResearchProject::count(),
            'co_supervised' => $staff
                ? PostgraduateResearchProject::where('co_supervisor_id', $staff->id)->orWhere('co_supervisor_2_id', $staff->id)->count()
                : PostgraduateResearchProject::whereNotNull('co_supervisor_id')->count(),
            'pending_logs' => $pendingLogs->count(),
            'defense_ready' => $staff
                ? PostgraduateResearchProject::where('major_supervisor_id', $staff->id)->where('status', 'defense_scheduled')->count()
                : PostgraduateResearchProject::where('status', 'defense_scheduled')->count(),
        ];

        return Inertia::render('Staff/Postgraduate/Supervision', [
            'projects' => $projects,
            'pendingLogs' => $pendingLogs,
            'stats' => $stats,
            'staff' => $staff ? $staff->load('user') : [
                'id' => 'spgs-admin-view',
                'staff_number' => 'SPGS-DESK',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
            'isAdminView' => !$staff && $isAdmin,
        ]);
    }

    public function reviewLog(Request $request, PostgraduateResearchLog $log)
    {
        $user = $request->user();
        $staff = $user->staff;
        $isAdmin = $user->hasRole(['super_admin', 'admin', 'dean_spgs']) || $user->can('manage_postgraduate_school');

        if (!$staff && !$isAdmin) {
            abort(403, 'Academic staff profile required.');
        }

        $validated = $request->validate([
            'supervisor_status' => 'required|in:reviewed,needs_revision',
            'supervisor_feedback' => 'nullable|string|max:3000',
        ]);

        if (empty($validated['supervisor_feedback'])) {
            $validated['supervisor_feedback'] = $validated['supervisor_status'] === 'reviewed'
                ? 'Candidate research log entry verified and endorsed.'
                : 'Log entry noted; revisions requested.';
        }

        $this->researchService->reviewLogEntry($log, $staff, $validated);

        return back()->with('success', 'Candidate research log reviewed and feedback recorded.');
    }

    public function reviewMilestone(Request $request, PostgraduateResearchMilestone $milestone)
    {
        $user = $request->user();
        $staff = $user->staff;
        $isAdmin = $user->hasRole(['super_admin', 'admin', 'dean_spgs']) || $user->can('manage_postgraduate_school');

        if (!$staff && !$isAdmin) {
            abort(403, 'Academic staff profile required.');
        }

        $validated = $request->validate([
            'status' => 'required|in:supervisor_approved,revision_requested,in_progress',
            'supervisor_remarks' => 'nullable|string|max:3000',
            'score' => 'nullable|numeric|min:0|max:100',
            'plagiarism_similarity_index' => 'nullable|numeric|min:0|max:100',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:25600',
        ]);

        if (empty($validated['supervisor_remarks'])) {
            $validated['supervisor_remarks'] = $validated['status'] === 'supervisor_approved'
                ? 'Milestone evaluated and cleared by supervisory committee.'
                : ($validated['status'] === 'revision_requested' ? 'Stage revisions required before proceeding.' : 'Under supervisory review.');
        }

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('postgraduate/milestones', 'public');
            $validated['document_path'] = $path;
        }

        $this->researchService->reviewMilestone($milestone, $validated, $user->id);

        return back()->with('success', 'Milestone evaluation and stage manuscript submitted successfully.');
    }

    public function endorseProgressReport(Request $request, PostgraduateProgressReport $progressReport)
    {
        $user = $request->user();
        $staff = $user->staff;
        $isAdmin = $user->hasRole(['super_admin', 'admin', 'dean_spgs']) || $user->can('manage_postgraduate_school');

        if (!$staff && !$isAdmin) {
            abort(403, 'Academic staff profile required.');
        }

        $validated = $request->validate([
            'supervisor_rating' => 'required|in:excellent,satisfactory,needs_improvement,unsatisfactory',
            'supervisor_comments' => 'nullable|string|max:3000',
        ]);

        if (empty($validated['supervisor_comments'])) {
            $validated['supervisor_comments'] = 'Candidate annual research velocity evaluated and endorsed.';
        }

        $this->candidacyService->reviewProgressReportBySupervisor($progressReport, $staff, $validated);

        return back()->with('success', 'Annual Progress Report signed off and rated.');
    }
}
