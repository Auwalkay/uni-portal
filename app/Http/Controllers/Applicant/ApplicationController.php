<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Faculty;
use App\Models\SystemSetting;
use DB;
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

        $applicant = Applicant::where('user_id', $user->id)
            ->with(['programme'])
            ->first();

        $applicationInvoice = \App\Models\Invoice::with('session')
            ->where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->latest()
            ->first();

        $currentSession = \App\Services\AcademicCacheService::getCurrentSession();

        // If applicant submitted an application but application invoice is missing, generate it
        if ($applicant && (!$applicationInvoice || $applicationInvoice->status !== 'paid') && $applicant->status === 'pending_payment') {
            if (!$applicationInvoice && $currentSession) {
                $applicationInvoice = \App\Models\Invoice::create([
                    'user_id' => $user->id,
                    'type' => 'application_fee',
                    'session_id' => $currentSession->id,
                    'reference' => 'APP-' . strtoupper(uniqid()),
                    'amount' => SystemSetting::get('application_fee', 100000),
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]);

                $applicationInvoice->items()->create([
                    'description' => 'Application Fee',
                    'amount' => SystemSetting::get('application_fee', 100000),
                    'quantity' => 1,
                ]);

                $applicationInvoice->load('session');
            }
        }

        $acceptanceInvoice = null;
        if ($applicant && $applicant->status === 'admitted') {
            $acceptanceInvoice = \App\Models\Invoice::where('user_id', $user->id)
                ->where('type', 'acceptance_fee')
                ->where('status', 'pending')
                ->first();
        }

        $hasPaidApplicationFee = $applicationInvoice && $applicationInvoice->status === 'paid';

        return Inertia::render('Applicant/Dashboard', [
            'applicant' => $applicant,
            'applicationInvoice' => $applicationInvoice,
            'invoice' => $acceptanceInvoice ?? $applicationInvoice,
            'hasPaidApplicationFee' => $hasPaidApplicationFee,
            'currentSession' => $currentSession,
        ]);
    }

    public function create()
    {
        return Inertia::render('Applicant/Application/Start', [
            'faculties' => \App\Services\AcademicCacheService::getFacultiesWithProgrammes(true),
            'currentSession' => \App\Services\AcademicCacheService::getCurrentSession(),
        ]);
    }

    public function form(Request $request)
    {
        $programme = null;
        if ($request->query('programme_id')) {
            $programme = \App\Models\Programme::where('is_active', true)->find($request->query('programme_id'));
        }

        return Inertia::render('Applicant/Application/FormWizard', [
            'mode' => $request->query('mode', 'UTME'),
            'programme_id' => $request->query('programme_id'),
            'programme' => $programme,
            'states' => \App\Services\AcademicCacheService::getStates(),
            'currentSession' => \App\Services\AcademicCacheService::getCurrentSession(),
        ]);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'dob' => 'required|date',
                'phone' => 'required|string',
                'jamb_score' => 'required|numeric',
                'previous_institution' => 'nullable|string',
                'programme_id' => [
                    'required',
                    \Illuminate\Validation\Rule::exists('programmes', 'id')->where('is_active', true),
                ],
                'mode' => 'required|string',
                'state_id' => 'required|exists:states,id',
                'lga_id' => 'required|exists:lgas,id',
                'next_of_kin_name' => 'required|string',
                'next_of_kin_phone' => 'required|string',
                'next_of_kin_relationship' => 'required|string',
                'passport_photo' => 'nullable|file|mimes:jpeg,png,jpg|max:500',
                'waec_result' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:500',
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
                    'program_choice_1' => $request->input('programme_id'),
                    'status' => 'pending_payment',

                    // Personal
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'dob' => $request->dob,
                    'phone' => $request->phone,
                    'gender' => $request->gender,

                    // Origin
                    'state_id' => $request->state_id,
                    'lga_id' => $request->lga_id,

                    // Academic
                    'jamb_score' => $request->jamb_score,

                    // NOK
                    'next_of_kin_name' => $request->next_of_kin_name,
                    'next_of_kin_phone' => $request->next_of_kin_phone,
                    'next_of_kin_relationship' => $request->next_of_kin_relationship,
                ]
            );

            // Handle File Uploads
            if ($request->hasFile('passport_photo')) {
                $path = $request->file('passport_photo')
                    ->store('applicants/passports', 'public');

                $applicant->documents()->create([
                    'type' => 'passport_photo',
                    'path' => $path,
                    'status' => 'uploaded',
                ]);
            }

            if ($request->hasFile('waec_result')) {
                $path = $request->file('waec_result')
                    ->store('applicants/results', 'public');

                $applicant->documents()->create([
                    'type' => 'waec_result',
                    'path' => $path,
                    'status' => 'uploaded',
                ]);
            }

            // Generate Invoice
            $currentSession = \App\Models\Session::current();
            if (!$currentSession) {
                throw new \Exception('No active academic session found.');
            }

            $invoice = \App\Models\Invoice::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'application_fee',
                    'session_id' => $currentSession->id,
                ],
                [
                    'reference' => 'APP-' . strtoupper(uniqid()),
                    'amount' => SystemSetting::get('application_fee', 100000),
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]
            );

            if ($invoice->items()->count() === 0) {
                $invoice->items()->create([
                    'description' => 'Application Fee',
                    'amount' => SystemSetting::get('application_fee', 100000),
                    'quantity' => 1,
                ]);
            }

            return redirect()
                ->route('applicant.payment.index')
                ->with('success', 'Application saved. Please pay the application fee to complete submission.');
        });
    }

    public function acceptOffer(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Accept Offer Hit by User: ' . $request->user()->id);
        $user = $request->user();

        // Check if already a student
        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard')->with('info', 'You have already accepted the offer.');
        }

        $applicant = Applicant::where('user_id', $user->id)->first();

        if (!$applicant || $applicant->status !== 'admitted') {
            return back()->with('error', 'Invalid request. You must be admitted to accept.');
        }

        try {
            app(\App\Services\EnrollmentService::class)->enroll($applicant, $user->id);

            return redirect()->route('student.dashboard')->with('success', 'Admission accepted! Welcome to the university.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Enrollment failed: ' . $e->getMessage());

            return back()->with('error', 'An error occurred while processing acceptance.');
        }
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $applicant = Applicant::where('user_id', $user->id)
            ->with(['programme', 'state', 'lga', 'documents'])
            ->first();

        if (!$applicant) {
            return redirect()->route('applicant.apply.start');
        }

        $invoice = \App\Models\Invoice::with('session')
            ->where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->latest()
            ->first();

        return Inertia::render('Applicant/Application/Show', [
            'applicant' => $applicant,
            'invoice' => $invoice,
            'hasPaidApplicationFee' => $invoice && $invoice->status === 'paid',
            'currentSession' => \App\Services\AcademicCacheService::getCurrentSession(),
        ]);
    }
}
