<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StudentAdmitted;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Services\Admissions\PostgraduateAdmissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class PostgraduateAdmissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->can('view_pg_applications') && ! $user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Unauthorized to view postgraduate applications.');
        }

        $query = Applicant::postgraduate()->with(['user', 'programme.department.faculty', 'referees', 'qualifications']);

        // Stats
        $stats = [
            'total' => Applicant::postgraduate()->count(),
            'admitted' => Applicant::postgraduate()->where('status', 'admitted')->count(),
            'rejected' => Applicant::postgraduate()->where('status', 'rejected')->count(),
            'pending' => Applicant::postgraduate()->where('status', 'pending')->count(),
            'screening' => Applicant::postgraduate()->where('status', 'screening')->count(),
        ];

        // Filters
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                })->orWhere('application_number', 'like', '%'.$search.'%')
                  ->orWhere('jamb_registration_number', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('programme_id')) {
            $query->where('program_choice_1', $request->programme_id);
        }

        if ($request->filled('tier')) {
            $tier = strtoupper(trim($request->tier));
            $query->whereHas('programme', function ($pq) use ($tier) {
                $pq->where('type', $tier);
            });
        }

        $perPage = min(max($request->integer('per_page', 15), 5), 100);
        $applicants = $query->latest()->paginate($perPage)->withQueryString();

        $programmes = \App\Services\AcademicCacheService::getProgrammes()
            ->filter(fn ($p) => $p->isPostgraduate())
            ->values();

        $filters = $request->only(['search', 'status', 'programme_id', 'tier']);
        $filters['per_page'] = $perPage;

        $userPermissions = [
            'can_view_ug' => $user->can('view_applications') || $user->hasRole(['admin', 'super_admin']),
            'can_view_pg' => true,
            'can_review' => $user->can('review_pg_applications') || $user->hasRole(['admin', 'super_admin']),
            'can_admit' => $user->can('admit_pg_students') || $user->hasRole(['admin', 'super_admin']),
        ];

        return Inertia::render('Admin/Postgraduate/Admissions/Index', [
            'applicants' => $applicants,
            'stats' => $stats,
            'filters' => $filters,
            'programmes' => $programmes,
            'user_permissions' => $userPermissions,
        ]);
    }

    public function show(Request $request, Applicant $applicant)
    {
        $user = $request->user();

        if (! $applicant->isPostgraduate()) {
            if ($user->can('view_applications') || $user->hasRole(['admin', 'super_admin'])) {
                return redirect()->route('admin.admissions.ug.show', $applicant);
            }
            abort(404, 'Postgraduate applicant record not found.');
        }

        if (! $user->can('view_pg_applications') && ! $user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Unauthorized to view postgraduate applications.');
        }

        $applicant->load([
            'user',
            'documents',
            'referees',
            'qualifications',
            'employments',
            'state',
            'lga',
            'programme.department.faculty',
        ]);

        $paymentInfo = Invoice::where('user_id', $applicant->user_id)
            ->where('type', 'application_fee')
            ->with('payments')
            ->first();

        $pgEligibility = null;
        $pgTier = null;
        $defaultLevel = 700;

        if ($applicant->programme) {
            $pgService = app(PostgraduateAdmissionService::class);
            $pgTier = $pgService->resolveProgrammeTier($applicant->programme);
            $defaultLevel = $pgService->resolveDefaultLevel($applicant->programme);

            if ($applicant->qualifications->isNotEmpty()) {
                $pgEligibility = $pgService->validateQualificationsList(
                    $applicant->programme,
                    $applicant->qualifications->toArray()
                );
            } elseif ($applicant->previous_qualification && $applicant->previous_class_of_degree) {
                $pgEligibility = $pgService->validateEntryQualification(
                    $applicant->programme,
                    $applicant->previous_qualification,
                    $applicant->previous_class_of_degree,
                    $applicant->previous_cgpa
                );
            }
        }

        $userPermissions = [
            'can_view_ug' => $user->can('view_applications') || $user->hasRole(['admin', 'super_admin']),
            'can_view_pg' => true,
            'can_review' => $user->can('review_pg_applications') || $user->hasRole(['admin', 'super_admin']),
            'can_admit' => $user->can('admit_pg_students') || $user->hasRole(['admin', 'super_admin']),
        ];

        return Inertia::render('Admin/Postgraduate/Admissions/Show', [
            'applicant' => $applicant,
            'payment_info' => $paymentInfo,
            'pg_eligibility' => $pgEligibility,
            'pg_tier' => $pgTier,
            'default_level' => $defaultLevel,
            'programmes' => fn () => \App\Services\AcademicCacheService::getProgrammes()->filter(fn ($p) => $p->isPostgraduate())->values(),
            'scholarships' => fn () => \App\Services\AcademicCacheService::getScholarships(),
            'user_permissions' => $userPermissions,
        ]);
    }

    public function update(Request $request, Applicant $applicant)
    {
        $user = $request->user();

        if (! $applicant->isPostgraduate()) {
            abort(403, 'Not a postgraduate applicant.');
        }

        if (! $user->can('review_pg_applications') && ! $user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Unauthorized to review postgraduate applications.');
        }

        if ($request->status === 'admitted') {
            if (! $user->can('admit_pg_students') && ! $user->hasRole(['admin', 'super_admin'])) {
                abort(403, 'Unauthorized to admit postgraduate students.');
            }
        }

        $rules = [
            'status' => 'required|string|in:draft,submitted,screening,admitted,rejected',
        ];

        if ($request->status === 'admitted') {
            $rules['admitted_level'] = 'required|in:700,800,900';
            $rules['admitted_programme_id'] = 'required|exists:programmes,id';
            $rules['scholarship_id'] = 'nullable|exists:scholarships,id';
        }

        $validated = $request->validate($rules);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'admitted') {
            $updateData['admitted_level'] = $validated['admitted_level'];
            $updateData['admitted_programme_id'] = $validated['admitted_programme_id'];
            $updateData['scholarship_id'] = $validated['scholarship_id'] ?? null;
        }

        $applicant->update($updateData);

        if ($validated['status'] === 'admitted') {
            $applicant->load('user');
            try {
                Mail::to($applicant->user->email)->send(new StudentAdmitted($applicant));
                Log::info("SPGS Admission email sent for applicant: {$applicant->application_number}");
            } catch (\Throwable $e) {
                Log::error("Failed sending SPGS admission email: {$e->getMessage()}");
            }
        }

        return back()->with('success', 'Postgraduate applicant status updated successfully.');
    }

    public function downloadLetter(Request $request, Applicant $applicant)
    {
        $user = $request->user();

        if (! $applicant->isPostgraduate()) {
            abort(404, 'Not a postgraduate applicant.');
        }

        if (! $user->can('view_pg_applications') && ! $user->hasRole(['admin', 'super_admin'])) {
            abort(403, 'Unauthorized to view postgraduate admission letters.');
        }

        if ($applicant->status !== 'admitted') {
            abort(403, 'Admission letter is only available for admitted applicants.');
        }

        $identifier = $applicant->application_number ?? $applicant->jamb_registration_number ?? 'SPGS_Letter';
        $fileName = "SPGS_Admission_Letter_{$identifier}.pdf";
        $filePath = "admission_letters/{$applicant->user_id}.pdf";

        $applicant->load(['user', 'programme.department.faculty', 'state', 'lga', 'scholarship']);
        $currentSession = \App\Models\Session::current();

        $pdf = Pdf::loadView('documents.admission_letter', [
            'applicant' => $applicant,
            'faculty_name' => $applicant->programme?->department->faculty->name ?? 'School of Postgraduate Studies',
            'programme_name' => $applicant->programme?->name ?? 'N/A',
            'session_name' => $currentSession->name ?? '2025/2026',
            'fees' => null,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf->download($fileName);
    }
}

