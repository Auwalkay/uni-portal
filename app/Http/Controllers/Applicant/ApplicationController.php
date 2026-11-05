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
            ->with(['programme', 'referees', 'qualifications', 'employments'])
            ->first();

        $applicationInvoice = \App\Models\Invoice::with('session')
            ->where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->latest()
            ->first();

        $currentSession = ($applicant?->programme ? \App\Models\Session::currentForProgram($applicant->programme) : null)
            ?? \App\Services\AcademicCacheService::getCurrentSession();

        // If applicant submitted an application but application invoice is missing, generate it
        if ($applicant && (!$applicationInvoice || $applicationInvoice->status !== 'paid') && $applicant->status === 'pending_payment') {
            if (!$applicationInvoice && $currentSession) {
                $appFee = $applicant->isPostgraduate()
                    ? SystemSetting::get('pg_application_fee', 50000)
                    : SystemSetting::get('application_fee', 100000);

                $applicationInvoice = \App\Models\Invoice::create([
                    'user_id' => $user->id,
                    'type' => 'application_fee',
                    'session_id' => $currentSession->id,
                    'reference' => 'APP-' . strtoupper(uniqid()),
                    'amount' => $appFee,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]);

                $applicationInvoice->items()->create([
                    'description' => $applicant->isPostgraduate() ? 'Postgraduate Application & Enrolment Fee' : 'Application Fee',
                    'amount' => $appFee,
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

        $mode = $request->query('mode', ($programme && $programme->isPostgraduate()) ? 'PG' : 'UTME');
        $pgService = app(\App\Services\Admissions\PostgraduateAdmissionService::class);
        $isPostgraduate = in_array(strtoupper((string) $mode), ['PG', 'POSTGRADUATE'], true) || ($programme && $programme->isPostgraduate());
        $currentSession = ($programme ? \App\Models\Session::currentForProgram($programme) : null)
            ?? \App\Services\AcademicCacheService::getCurrentSession();

        return Inertia::render('Applicant/Application/FormWizard', [
            'mode' => $mode,
            'isPostgraduate' => $isPostgraduate,
            'pgTier' => ($programme && $isPostgraduate) ? $pgService->resolveProgrammeTier($programme) : null,
            'programme_id' => $request->query('programme_id'),
            'programme' => $programme,
            'states' => \App\Services\AcademicCacheService::getStates(),
            'currentSession' => $currentSession,
            'qualifications' => \App\Services\Admissions\PostgraduateAdmissionService::QUALIFICATIONS,
            'degreeClasses' => \App\Services\Admissions\PostgraduateAdmissionService::DEGREE_CLASSES,
        ]);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $programme = $request->filled('programme_id')
                ? \App\Models\Programme::where('is_active', true)->find($request->input('programme_id'))
                : null;

            $mode = (string) $request->input('mode', 'UTME');
            $isPg = in_array(strtoupper($mode), ['PG', 'POSTGRADUATE'], true);

            if ($programme) {
                if ($isPg && !$programme->isPostgraduate()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'programme_id' => 'The selected programme (' . $programme->name . ') is an Undergraduate programme and cannot be selected for Postgraduate admission.',
                    ]);
                }
                if (!$isPg && $programme->isPostgraduate()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'programme_id' => 'The selected programme (' . $programme->name . ') is a Postgraduate programme. Please switch to Postgraduate application mode.',
                    ]);
                }
            }

            $hasQualificationsArray = is_array($request->input('qualifications')) && count($request->input('qualifications')) > 0;

            $rules = [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'dob' => 'required|date',
                'phone' => 'required|string',
                'jamb_score' => $isPg ? 'nullable|numeric' : 'required|numeric',
                'previous_institution' => ($isPg && !$hasQualificationsArray) ? 'required|string|max:255' : 'nullable|string|max:255',
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
                'passport_photo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                'waec_result' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
            ];

            if ($isPg) {
                $rules = array_merge($rules, [
                    'title' => 'nullable|string|max:20',
                    'marital_status' => 'nullable|string|max:30',
                    'nationality' => 'nullable|string|max:100',
                    'has_name_change' => 'nullable|boolean',
                    'previous_full_name' => 'nullable|string|max:255',
                    'study_mode' => 'nullable|string|in:full_time,part_time,executive',
                    'area_of_specialization' => 'nullable|string|max:255',
                    'sponsorship_type' => 'nullable|string|in:self,tetfund,ptdf,employer,scholarship,other',
                    'sponsor_name' => 'nullable|string|max:255',
                    'nysc_certificate_number' => 'nullable|string|max:100',
                    'official_transcript_status' => 'nullable|string|max:50',
                    'previous_qualification' => $hasQualificationsArray ? 'nullable|string|max:100' : 'required|string|max:100',
                    'previous_course_of_study' => 'nullable|string|max:255',
                    'previous_class_of_degree' => $hasQualificationsArray ? 'nullable|string|max:100' : 'required|string|max:100',
                    'previous_cgpa' => 'nullable|numeric|min:0|max:5.00',
                    'graduation_year' => 'nullable|integer|min:1960|max:' . ((int) date('Y') + 1),
                    'qualifications' => 'nullable|array|min:1',
                    'qualifications.*.qualification_type' => 'required_with:qualifications|string|max:100',
                    'qualifications.*.institution_name' => 'required_with:qualifications|string|max:255',
                    'qualifications.*.course_of_study' => 'required_with:qualifications|string|max:255',
                    'qualifications.*.class_of_degree' => 'required_with:qualifications|string|max:100',
                    'qualifications.*.cgpa' => 'nullable|numeric|min:0|max:5.00',
                    'qualifications.*.scale_max' => 'nullable|numeric|min:4.00|max:5.00',
                    'qualifications.*.graduation_year' => 'required_with:qualifications|integer|min:1960|max:' . ((int) date('Y') + 1),
                    'qualifications.*.certificate_number' => 'nullable|string|max:100',
                    'employments' => 'nullable|array',
                    'employments.*.employer_name' => 'nullable|string|max:255',
                    'employments.*.job_title' => 'nullable|string|max:255',
                    'employments.*.department_or_unit' => 'nullable|string|max:255',
                    'employments.*.start_date' => 'nullable|date',
                    'employments.*.end_date' => 'nullable|date',
                    'employments.*.is_current' => 'nullable|boolean',
                    'employments.*.responsibilities' => 'nullable|string|max:2000',
                    'nysc_status' => 'nullable|string|max:50',
                    'work_experience_years' => 'nullable|integer|min:0|max:60',
                    'statement_of_purpose' => 'nullable|string|max:5000',
                    'research_proposal_title' => 'nullable|string|max:500',
                    'academic_transcript' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
                    'degree_certificate' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
                    'nysc_certificate' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
                    'cv_resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
                    'concept_note' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
                    'name_change_evidence' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
                    'referees' => 'required|array|size:3',
                    'referees.*.name' => 'required|string|max:150',
                    'referees.*.email' => 'required|email|distinct|max:150',
                    'referees.*.phone' => 'nullable|string|max:50',
                    'referees.*.institution_or_organization' => 'required|string|max:200',
                    'referees.*.position_or_rank' => 'nullable|string|max:150',
                    'referees.*.relationship' => 'nullable|string|max:150',
                ]);
            }

            $validated = $request->validate($rules);

            $pgService = app(\App\Services\Admissions\PostgraduateAdmissionService::class);
            $normalizedQualifications = [];

            if ($isPg) {
                if ($hasQualificationsArray) {
                    $normalizedQualifications = $request->input('qualifications');
                } else {
                    $normalizedQualifications = [[
                        'qualification_type' => (string) $request->input('previous_qualification'),
                        'institution_name' => (string) $request->input('previous_institution'),
                        'course_of_study' => (string) ($request->input('previous_course_of_study') ?? $request->input('previous_course') ?? 'General Studies'),
                        'class_of_degree' => (string) ($request->input('previous_class_of_degree') ?? $request->input('class_of_degree')),
                        'cgpa' => $request->filled('previous_cgpa') ? (float) $request->input('previous_cgpa') : null,
                        'scale_max' => 5.00,
                        'graduation_year' => $request->filled('graduation_year') ? (int) $request->input('graduation_year') : (int) date('Y'),
                    ]];
                }

                if ($programme) {
                    $eligibility = $pgService->validateQualificationsList($programme, $normalizedQualifications);

                    if (!$eligibility['eligible']) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'previous_qualification' => $eligibility['message'],
                        ]);
                    }
                }
            }

            $user = $request->user();

            // Ensure user has applicant role
            if (!$user->hasRole('applicant')) {
                $user->assignRole('applicant');
            }

            $defaultJambOrRef = $isPg
                ? ('PG-' . strtoupper(uniqid()))
                : ('PENDING-' . time());

            $primaryQual = $normalizedQualifications[0] ?? [];

            $applicant = Applicant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'jamb_registration_number' => $request->input('jamb_number') ?: $defaultJambOrRef,
                    'application_mode' => $isPg ? 'PG' : $request->input('mode'),
                    'program_choice_1' => $request->input('programme_id'),
                    'status' => 'pending_payment',

                    // Personal & Biodata
                    'title' => $request->input('title'),
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'dob' => $request->dob,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'gender' => $request->gender,
                    'marital_status' => $request->input('marital_status'),
                    'nationality' => $request->input('nationality', 'Nigerian'),
                    'has_name_change' => $request->boolean('has_name_change'),
                    'previous_full_name' => $request->input('previous_full_name'),

                    // Origin
                    'state_id' => $request->state_id,
                    'lga_id' => $request->lga_id,

                    // Academic & PG Qualification
                    'jamb_score' => $request->jamb_score,
                    'study_mode' => $request->input('study_mode') ?: ($isPg ? 'full_time' : 'Full-Time'),
                    'area_of_specialization' => $request->input('area_of_specialization'),
                    'sponsorship_type' => $request->input('sponsorship_type') ?: ($isPg ? 'self' : 'Self'),
                    'sponsor_name' => $request->input('sponsor_name'),
                    'previous_institution' => $request->input('previous_institution') ?: ($primaryQual['institution_name'] ?? null),
                    'previous_qualification' => $request->input('previous_qualification') ?: ($primaryQual['qualification_type'] ?? null),
                    'previous_course_of_study' => $request->input('previous_course_of_study') ?: ($primaryQual['course_of_study'] ?? null),
                    'previous_class_of_degree' => $request->input('previous_class_of_degree') ?: ($primaryQual['class_of_degree'] ?? null),
                    'previous_cgpa' => $request->filled('previous_cgpa') ? (float) $request->previous_cgpa : ($primaryQual['cgpa'] ?? null),
                    'graduation_year' => $request->filled('graduation_year') ? (int) $request->graduation_year : ($primaryQual['graduation_year'] ?? null),
                    'nysc_status' => $request->nysc_status,
                    'nysc_certificate_number' => $request->input('nysc_certificate_number'),
                    'official_transcript_status' => $request->input('official_transcript_status') ?: 'not_requested',
                    'work_experience_years' => (int) $request->input('work_experience_years', 0),
                    'statement_of_purpose' => $request->statement_of_purpose,
                    'research_proposal_title' => $request->research_proposal_title,

                    // NOK
                    'next_of_kin_name' => $request->next_of_kin_name,
                    'next_of_kin_phone' => $request->next_of_kin_phone,
                    'next_of_kin_relationship' => $request->next_of_kin_relationship,
                ]
            );

            // Handle File Uploads
            $uploadMappings = [
                'passport_photo' => 'applicants/passports',
                'waec_result' => 'applicants/results',
                'academic_transcript' => 'applicants/transcripts',
                'degree_certificate' => 'applicants/certificates',
                'nysc_certificate' => 'applicants/nysc',
                'cv_resume' => 'applicants/cv',
                'concept_note' => 'applicants/proposals',
                'name_change_evidence' => 'applicants/name_change',
            ];

            foreach ($uploadMappings as $field => $folder) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store($folder, 'public');

                    $applicant->documents()->updateOrCreate(
                        ['type' => $field],
                        [
                            'path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getClientMimeType(),
                            'status' => 'uploaded',
                        ]
                    );
                }
            }

            if ($isPg) {
                if (!empty($normalizedQualifications)) {
                    $pgService->syncQualifications($applicant, $normalizedQualifications);
                }
                if (is_array($request->input('employments'))) {
                    $pgService->syncEmployments($applicant, $request->input('employments'));
                }
                if (is_array($request->input('referees'))) {
                    $pgService->syncReferees($applicant, $request->input('referees'));
                }
            }

            // Generate Invoice
            $currentSession = ($programme ? \App\Models\Session::currentForProgram($programme) : null)
                ?: \App\Models\Session::current();
            if (!$currentSession) {
                throw new \Exception('No active academic session found.');
            }

            $appFee = $applicant->isPostgraduate()
                ? SystemSetting::get('pg_application_fee', 50000)
                : SystemSetting::get('application_fee', 100000);

            $invoice = \App\Models\Invoice::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'application_fee',
                    'session_id' => $currentSession->id,
                ],
                [
                    'reference' => 'APP-' . strtoupper(uniqid()),
                    'amount' => $appFee,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]
            );

            if ($invoice->items()->count() === 0) {
                $invoice->items()->create([
                    'description' => $applicant->isPostgraduate() ? 'Postgraduate Application & Enrolment Fee' : 'Application Fee',
                    'amount' => $appFee,
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
            ->with(['programme', 'state', 'lga', 'documents', 'referees', 'qualifications', 'employments'])
            ->first();

        if (!$applicant) {
            return redirect()->route('applicant.apply.start');
        }

        $invoice = \App\Models\Invoice::with('session')
            ->where('user_id', $user->id)
            ->where('type', 'application_fee')
            ->latest()
            ->first();

        $currentSession = ($applicant->programme ? \App\Models\Session::currentForProgram($applicant->programme) : null)
            ?? \App\Services\AcademicCacheService::getCurrentSession();

        return Inertia::render('Applicant/Application/Show', [
            'applicant' => $applicant,
            'invoice' => $invoice,
            'hasPaidApplicationFee' => $invoice && $invoice->status === 'paid',
            'currentSession' => $currentSession,
        ]);
    }

    public function downloadTranscriptLabel(Request $request)
    {
        $user = $request->user();
        $applicant = Applicant::where('user_id', $user->id)
            ->with(['programme.department.faculty', 'qualifications'])
            ->firstOrFail();

        if (!$applicant->isPostgraduate()) {
            abort(403, 'Official Transcript Request Label is only applicable to Postgraduate applicants.');
        }

        if ($applicant->official_transcript_status === 'not_requested' || !$applicant->official_transcript_status) {
            $applicant->update(['official_transcript_status' => 'requested_by_applicant']);
        }

        $currentSession = ($applicant->programme ? \App\Models\Session::currentForProgram($applicant->programme) : null)
            ?? \App\Models\Session::current();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.pg_transcript_label', [
            'applicant' => $applicant,
            'qualifications' => $applicant->qualifications,
            'programme_name' => $applicant->programme?->name ?? 'Postgraduate Programme',
            'department_name' => $applicant->programme?->department?->name ?? 'Department',
            'faculty_name' => $applicant->programme?->department?->faculty?->name ?? 'School of Postgraduate Studies',
            'session_name' => $currentSession?->name ?? date('Y') . '/' . (date('Y') + 1),
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $ref = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $applicant->jamb_registration_number);

        return $pdf->download("SPGS_Transcript_Request_Label_{$ref}.pdf");
    }
}
