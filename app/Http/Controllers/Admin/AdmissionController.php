<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdmissionController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Admissions/Index', [
            'applicants' => Applicant::with('user')->latest()->paginate(10)
        ]);
    }

    public function show(Applicant $applicant)
    {
        $applicant->load(['user', 'documents']);
        return Inertia::render('Admin/Admissions/Show', [
            'applicant' => $applicant
        ]);
    }

    public function update(Request $request, Applicant $applicant)
    {
        $request->validate([
            'status' => 'required|string|in:draft,submitted,screening,admitted,rejected'
        ]);

        $applicant->update([
            'status' => $request->status
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
                    'reference' => 'ACC-' . strtoupper(uniqid()),
                    'amount' => 50000.00,
                    'status' => 'pending',
                    'due_date' => now()->addWeeks(2),
                ]
            );
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
