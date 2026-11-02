<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PostgraduateExternalExaminerRegistry;
use App\Models\PostgraduateStaffAccreditation;
use App\Models\Staff;
use App\Services\Postgraduate\PostgraduateStaffGovernanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostgraduateStaffController extends Controller
{
    public function __construct(
        protected PostgraduateStaffGovernanceService $staffGovernanceService
    ) {}

    public function index(Request $request)
    {
        $query = Staff::with(['user:id,name,email', 'department:id,name', 'postgraduateAccreditation'])
            ->where('is_academic', true);

        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('accreditation')) {
            if ($request->accreditation === 'phd') {
                $query->whereHas('postgraduateAccreditation', fn ($q) => $q->where('is_phd_accredited', true));
            } elseif ($request->accreditation === 'masters') {
                $query->whereHas('postgraduateAccreditation', fn ($q) => $q->where('is_masters_accredited', true));
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('staff_number', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $staffMembers = $query->orderBy('staff_number')->paginate(15)->withQueryString();

        // Attach capacity and SLA metrics to each staff item in current page
        $staffMembers->getCollection()->transform(function ($staff) {
            $accreditation = $this->staffGovernanceService->getOrCreateAccreditation($staff);
            $accreditation->recalculateActiveCounts();

            return [
                'id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'name' => $staff->user?->name ?? 'N/A',
                'email' => $staff->user?->email ?? 'N/A',
                'designation' => $staff->designation ?? 'Academic Staff',
                'department' => $staff->department?->name ?? 'N/A',
                'is_phd_accredited' => $accreditation->is_phd_accredited,
                'is_masters_accredited' => $accreditation->is_masters_accredited,
                'is_pgd_accredited' => $accreditation->is_pgd_accredited,
                'max_phd_quota' => $accreditation->max_phd_quota,
                'max_masters_quota' => $accreditation->max_masters_quota,
                'active_phd_count' => $accreditation->active_phd_count,
                'active_masters_count' => $accreditation->active_masters_count,
                'specialization_areas' => $accreditation->specialization_areas,
                'scopus_id' => $accreditation->scopus_id,
                'orcid_id' => $accreditation->orcid_id,
                'google_scholar_url' => $accreditation->google_scholar_url,
                'avg_turnaround_days' => $this->staffGovernanceService->computeSupervisorSlaDays($staff),
            ];
        });

        $externalExaminers = PostgraduateExternalExaminerRegistry::with(['firstAppointedSession:id,name', 'lastAppointedSession:id,name'])
            ->latest()
            ->paginate(15, ['*'], 'examiners_page')
            ->withQueryString();

        $stats = [
            'total_academic_staff' => Staff::where('is_academic', true)->count(),
            'phd_accredited' => PostgraduateStaffAccreditation::where('is_phd_accredited', true)->count(),
            'masters_accredited' => PostgraduateStaffAccreditation::where('is_masters_accredited', true)->count(),
            'total_external_examiners' => PostgraduateExternalExaminerRegistry::count(),
            'tenure_warning_examiners' => PostgraduateExternalExaminerRegistry::where('consecutive_sessions_count', '>=', 3)->count(),
        ];

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Postgraduate/Staff/Index', [
            'staffMembers' => $staffMembers,
            'externalExaminers' => $externalExaminers,
            'departments' => $departments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'department_id', 'accreditation']),
        ]);
    }

    public function updateAccreditation(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'is_phd_accredited' => 'required|boolean',
            'is_masters_accredited' => 'required|boolean',
            'is_pgd_accredited' => 'required|boolean',
            'max_phd_quota' => 'required|integer|min:1|max:20',
            'max_masters_quota' => 'required|integer|min:1|max:30',
            'specialization_areas' => 'nullable|array',
            'scopus_id' => 'nullable|string|max:100',
            'orcid_id' => 'nullable|string|max:100',
            'google_scholar_url' => 'nullable|url|max:255',
        ]);

        $this->staffGovernanceService->updateAccreditation($staff, $validated, $request->user());

        return back()->with('success', "Postgraduate supervisory accreditation updated for {$staff->user?->name}.");
    }

    public function searchSupervisors(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $progType = strtoupper((string) $request->get('tier', 'MSC'));

        $staffQuery = Staff::with(['user:id,name,email', 'department:id,name', 'postgraduateAccreditation'])
            ->where('is_academic', true);

        if (strlen($query) >= 2) {
            $staffQuery->where(function ($q) use ($query) {
                $q->where('staff_number', 'like', "%{$query}%")
                    ->orWhere('designation', 'like', "%{$query}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$query}%"));
            });
        }

        $results = $staffQuery->limit(15)->get()->map(function ($staff) use ($progType) {
            $accreditation = $this->staffGovernanceService->getOrCreateAccreditation($staff);
            $isPhd = ($progType === 'PHD');
            $activeLoad = $isPhd ? $accreditation->active_phd_count : $accreditation->active_masters_count;
            $maxQuota = $isPhd ? $accreditation->max_phd_quota : $accreditation->max_masters_quota;
            $isAccredited = $isPhd ? $accreditation->is_phd_accredited : $accreditation->is_masters_accredited;

            return [
                'id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'name' => $staff->user?->name ?? 'Staff',
                'designation' => $staff->designation ?? 'Academic Staff',
                'department' => $staff->department?->name ?? 'N/A',
                'is_accredited' => $isAccredited,
                'active_load' => $activeLoad,
                'max_quota' => $maxQuota,
                'has_capacity' => $isAccredited && ($activeLoad < $maxQuota),
                'label' => "{$staff->user?->name} ({$staff->designation}) • Load: {$activeLoad}/{$maxQuota}",
            ];
        });

        return response()->json($results);
    }

    public function storeExternalExaminer(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'specialization' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $this->staffGovernanceService->saveExternalExaminer($validated);

        return back()->with('success', 'External examiner successfully cataloged in the institutional registry.');
    }

    public function toggleExternalExaminer(PostgraduateExternalExaminerRegistry $examiner)
    {
        $examiner->update(['is_active' => !$examiner->is_active]);

        return back()->with('success', 'Examiner active status updated.');
    }
}

