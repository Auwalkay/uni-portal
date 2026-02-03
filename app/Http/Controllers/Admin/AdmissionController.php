<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Applicant::with(['user', 'programme']);

        // Stats
        $stats = [
            'total' => Applicant::count(),
            'admitted' => Applicant::where('status', 'admitted')->count(),
            'rejected' => Applicant::where('status', 'rejected')->count(),
            'pending' => Applicant::where('status', 'pending')->count(),
        ];

        // Filters
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            })->orWhere('jamb_registration_number', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        $applicants = $query->latest()->paginate(10)->withQueryString();

        $programmes = \App\Models\Programme::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Admissions/Index', [
            'applicants' => $applicants,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'programme_id']),
            'programmes' => $programmes,
        ]);
    }

    public function show(Applicant $applicant)
    {
        $applicant->load([
            'user',
            'documents',
            'state',
            'lga',
            'programme.department.faculty',
        ]);

        $paymentInfo = \App\Models\Invoice::where('user_id', $applicant->user_id)
            ->where('type', 'application_fee')
            ->with('payments') // Load successful payments check?
            ->first();

        return Inertia::render('Admin/Admissions/Show', [
            'applicant' => $applicant,
            'payment_info' => $paymentInfo,
        ]);
    }

    public function update(Request $request, Applicant $applicant)
    {
        $request->validate([
            'status' => 'required|string|in:draft,submitted,screening,admitted,rejected',
        ]);

        $applicant->update([
            'status' => $request->status,
        ]);

        // Optional: acceptance fee generation
        $chargeAcceptanceFee = false; // Set to true/config to enable

        if ($request->status === 'admitted' && $chargeAcceptanceFee) {
            \App\Models\Invoice::firstOrCreate(
                [
                    'user_id' => $applicant->user_id,
                    'type' => 'acceptance_fee',
                ],
                [
                    'reference' => 'ACC-'.strtoupper(uniqid()),
                    'amount' => 50000.00,
                    'status' => 'pending',
                    'due_date' => now()->addWeeks(2),
                ]
            );

            // Send Admission Email
            \Illuminate\Support\Facades\Mail::to($applicant->user->email)->send(new \App\Mail\StudentAdmitted($applicant));
            \Illuminate\Support\Facades\Log::info("Admission email queued for applicant: {$applicant->jamb_registration_number}");
        }

        return back()->with('success', 'Applicant status updated successfully.');
    }

    public function downloadLetter(Applicant $applicant)
    {
        if ($applicant->status !== 'admitted') {
            abort(403, 'Admission letter is only available for admitted applicants.');
        }

        $applicant->load(['user', 'programme.department.faculty']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.admission_letter', [
            'applicant' => $applicant,
            'faculty_name' => $applicant->programme?->department->faculty->name ?? 'N/A',
            'programme_name' => $applicant->programme?->name ?? 'N/A',
        ]);

        return $pdf->download("Admission_Letter_{$applicant->jamb_registration_number}.pdf");
    }
}
