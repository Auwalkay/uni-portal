<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('applicant')) {
            // Redirect or handle invalid role ??
        }

        $applicant = Applicant::where('user_id', $user->id)->first();

        $invoice = null;
        if ($applicant && $applicant->status === 'admitted') {
            $invoice = \App\Models\Invoice::where('user_id', $user->id)
                ->where('type', 'acceptance_fee')
                ->where('status', 'pending')
                ->first();
        }

        // If no applicant profile, maybe redirect to start page?
        return Inertia::render('Applicant/Dashboard', [
            'applicant' => $applicant,
            'invoice' => $invoice
        ]);
    }

    public function create()
    {
        return Inertia::render('Applicant/Application/Start', [
            'faculties' => Faculty::with('departments.programmes')->get()
        ]);
    }

    public function form(Request $request)
    {
        return Inertia::render('Applicant/Application/FormWizard', [
            'mode' => $request->query('mode', 'UTME'),
            'programme_id' => $request->query('programme_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'dob' => 'required|date',
            'phone' => 'required|string',
            'jamb_score' => 'required|numeric',
            'previous_institution' => 'nullable|string',
            'programme_id' => 'required|exists:programmes,id',
            'mode' => 'required|string',
            'passport_photo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'waec_result' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // Ensure user has applicant role
        if (!$user->hasRole('applicant')) {
            $user->assignRole('applicant');
        }

        $applicant = Applicant::updateOrCreate(
            ['user_id' => $user->id],
            [
                'jamb_registration_number' => $request->input('jamb_number', 'PENDING-' . time()),
                'application_mode' => $request->input('mode'),
                'program_choice_1' => $request->input('programme_id'), // Use program choice 1 for now
                'status' => 'submitted',
            ]
        );

        // Handle File Uploads
        if ($request->hasFile('passport_photo')) {
            $path = $request->file('passport_photo')->store('applicants/passports', 'public');
            $applicant->documents()->create([
                'type' => 'passport_photo',
                'path' => $path,
                'status' => 'uploaded'
            ]);
        }

        if ($request->hasFile('waec_result')) {
            $path = $request->file('waec_result')->store('applicants/results', 'public');
            $applicant->documents()->create([
                'type' => 'waec_result',
                'path' => $path,
                'status' => 'uploaded'
            ]);
        }

        return redirect()->route('applicant.dashboard')->with('success', 'Application submitted successfully!');
    }

    public function acceptOffer(Request $request)
    {
        $user = $request->user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        if (!$applicant || $applicant->status !== 'admitted') {
            return back()->with('error', 'Invalid request.');
        }

        // Check if invoice exists (if they need to pay, they shouldn't use this route)
        // But for flexibility, we proceed if configured to allow direct acceptance

        app(\App\Services\EnrollmentService::class)->enroll($applicant, $user->id);

        return redirect()->route('student.dashboard')->with('success', 'Admission accepted! Welcome to the University.');
    }
}
