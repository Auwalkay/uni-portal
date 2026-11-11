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
            ->with(['programme.department.faculty', 'referees', 'qualifications', 'employments', 'documents'])
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
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'support' => [
                'email' => 'admission@miu.edu.ng',
                'phone' => '+234 818 301 2911',
                'alt_phone' => '+234 810 804 0392',
                'address' => 'Km21, Kuchikau I, Abuja - Keffi Expressway',
                'portal_url' => 'www.miu.edu.ng',
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Applicant/Application/Start', [
            'faculties' => \App\Services\AcademicCacheService::getFacultiesWithProgrammes(true),
            'currentSession' => \App\Services\AcademicCacheService::getCurrentSession(),
        ]);
    }

    public function selectProgramme(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|string|in:UTME,Direct Entry,Transfer,Postgraduate,PG,DE',
            'programme_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('programmes', 'id')->where('is_active', true),
            ],
        ]);

        $user = $request->user();
        if (! $user->hasRole('applicant')) {
            $user->assignRole('applicant');
        }

        $programme = \App\Models\Programme::findOrFail($validated['programme_id']);
        $mode = $validated['mode'];
        if ($mode === 'PG') { $mode = 'Postgraduate'; }
        if ($mode === 'DE') { $mode = 'Direct Entry'; }

        $isPg = in_array(strtoupper($mode), ['POSTGRADUATE', 'PG'], true);

        if ($isPg && ! $programme->isPostgraduate()) {
            return back()->withErrors([
                'programme_id' => 'The selected programme (' . $programme->name . ') is an Undergraduate programme and cannot be selected for Postgraduate admission.',
            ]);
        }

        if (! $isPg && $programme->isPostgraduate()) {
            return back()->withErrors([
                'programme_id' => 'The selected programme (' . $programme->name . ') is a Postgraduate programme. Please switch to Postgraduate application mode.',
            ]);
        }

        $applicant = Applicant::where('user_id', $user->id)->first();

        if ($applicant && in_array($applicant->status, ['submitted', 'screening', 'admitted', 'enrolled'])) {
            return redirect()->route('applicant.apply.show')->with('info', 'Your application has already been submitted.');
        }

        if (! $applicant) {
            $applicant = Applicant::create([
                'user_id' => $user->id,
                'application_mode' => $isPg ? 'PG' : $mode,
                'program_choice_1' => $programme->id,
                'status' => 'pending_payment',
            ]);
        } else {
            $applicant->update([
                'application_mode' => $isPg ? 'PG' : $mode,
                'program_choice_1' => $programme->id,
                'status' => $applicant->hasPaidApplicationFee() ? 'draft' : 'pending_payment',
            ]);
        }

        // Generate / synchronize pending application fee invoice
        $session = \App\Models\Session::currentForProgram($programme)
            ?? \App\Services\AcademicCacheService::getCurrentSession();

        if ($session && ! $applicant->hasPaidApplicationFee()) {
            $appFee = $programme->isPostgraduate()
                ? SystemSetting::get('pg_application_fee', 50000)
                : SystemSetting::get('application_fee', 100000);

            $invoice = \App\Models\Invoice::where('user_id', $user->id)
                ->where('type', 'application_fee')
                ->where('status', '!=', 'paid')
                ->latest()
                ->first();

            if (! $invoice) {
                $invoice = \App\Models\Invoice::create([
                    'user_id' => $user->id,
                    'type' => 'application_fee',
                    'session_id' => $session->id,
                    'reference' => 'APP-' . strtoupper(uniqid()),
                    'amount' => $appFee,
                    'paid_amount' => 0,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]);

                $invoice->items()->create([
                    'description' => $programme->isPostgraduate() ? 'Postgraduate Application & Enrolment Fee' : 'Application Fee',
                    'amount' => $appFee,
                    'quantity' => 1,
                ]);
            } else {
                $invoice->update([
                    'amount' => $appFee,
                    'session_id' => $session->id,
                ]);
                $invoice->items()->first()?->update([
                    'description' => $programme->isPostgraduate() ? 'Postgraduate Application & Enrolment Fee' : 'Application Fee',
                    'amount' => $appFee,
                ]);
            }
        }

        if ($applicant->hasPaidApplicationFee()) {
            return redirect()->route('applicant.apply.form')->with('success', 'Programme choice saved. Please complete your application details.');
        }

        return redirect()->route('applicant.payment.index')->with('success', 'Programme choice saved. Please pay the application fee to proceed with your application.');
    }

    public function form(Request $request)
    {
        $user = $request->user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        // 1. Ensure user has chosen a programme first
        if (! $applicant || ! $applicant->program_choice_1) {
            return redirect()->route('applicant.apply.start')->with('warning', 'Please select your preferred programme of study first.');
        }

        // 2. Ensure application fee has been paid
        if (! $applicant->hasPaidApplicationFee()) {
            return redirect()->route('applicant.payment.index')->with('warning', 'Please pay the application fee before filling out your application details.');
        }

        $programme = \App\Models\Programme::where('is_active', true)->find($applicant->program_choice_1);
        if (! $programme) {
            return redirect()->route('applicant.apply.start')->with('error', 'Selected programme is invalid or inactive. Please choose a programme.');
        }

        $mode = $applicant->application_mode ?? (($programme && $programme->isPostgraduate()) ? 'PG' : 'UTME');
        $pgService = app(\App\Services\Admissions\PostgraduateAdmissionService::class);
        $isPostgraduate = $applicant->isPostgraduate();
        $currentSession = ($programme ? \App\Models\Session::currentForProgram($programme) : null)
            ?? \App\Services\AcademicCacheService::getCurrentSession();

        return Inertia::render('Applicant/Application/FormWizard', [
            'applicant' => $applicant->load(['programme.department.faculty', 'state', 'lga', 'documents', 'referees', 'qualifications', 'employments']),
            'mode' => $mode,
            'isPostgraduate' => $isPostgraduate,
            'pgTier' => ($programme && $isPostgraduate) ? $pgService->resolveProgrammeTier($programme) : null,
            'programme_id' => $programme->id,
            'programme' => $programme,
            'states' => \App\Services\AcademicCacheService::getStates(),
            'currentSession' => $currentSession,
            'qualifications' => \App\Services\Admissions\PostgraduateAdmissionService::QUALIFICATIONS,
            'degreeClasses' => \App\Services\Admissions\PostgraduateAdmissionService::DEGREE_CLASSES,
        ]);
    }

    public function saveStep(Request $request)
    {
        $user = $request->user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            return response()->json(['error' => 'Application has not been initiated. Please choose a programme first.'], 422);
        }

        if (in_array($applicant->status, ['submitted', 'screening', 'admitted', 'enrolled'])) {
            return response()->json(['error' => 'Application has already been submitted and cannot be edited.'], 403);
        }

        $updateData = [];

        // Biodata / Personal fields
        if ($request->has('first_name')) { $updateData['first_name'] = $request->input('first_name'); }
        if ($request->has('last_name')) { $updateData['last_name'] = $request->input('last_name'); }
        if ($request->has('other_names')) { $updateData['other_names'] = $request->input('other_names'); }
        if ($request->has('dob')) { $updateData['dob'] = $request->input('dob'); }
        if ($request->has('phone')) { $updateData['phone'] = $request->input('phone'); }
        if ($request->has('gender')) { $updateData['gender'] = $request->input('gender'); }
        if ($request->has('title')) { $updateData['title'] = $request->input('title'); }
        if ($request->has('marital_status')) { $updateData['marital_status'] = $request->input('marital_status'); }
        if ($request->has('nationality')) { $updateData['nationality'] = $request->input('nationality'); }
        if ($request->has('has_name_change')) { $updateData['has_name_change'] = $request->boolean('has_name_change'); }
        if ($request->has('previous_full_name')) { $updateData['previous_full_name'] = $request->input('previous_full_name'); }
        if ($request->has('address')) { $updateData['address'] = $request->input('address'); }
        if ($request->has('state_id')) { $updateData['state_id'] = $request->input('state_id') ?: null; }
        if ($request->has('lga_id')) { $updateData['lga_id'] = $request->input('lga_id') ?: null; }
        if ($request->has('next_of_kin_name')) { $updateData['next_of_kin_name'] = $request->input('next_of_kin_name'); }
        if ($request->has('next_of_kin_phone')) { $updateData['next_of_kin_phone'] = $request->input('next_of_kin_phone'); }
        if ($request->has('next_of_kin_relationship')) { $updateData['next_of_kin_relationship'] = $request->input('next_of_kin_relationship'); }

        // JAMB / Academic / Pathway fields
        if ($request->has('jamb_score')) { $updateData['jamb_score'] = $request->filled('jamb_score') ? $request->input('jamb_score') : null; }
        if ($request->filled('jamb_number') || $request->filled('jamb_registration_number')) {
            $updateData['jamb_registration_number'] = trim((string) ($request->input('jamb_number') ?: $request->input('jamb_registration_number')));
        }
        if ($request->has('study_mode')) { $updateData['study_mode'] = $request->input('study_mode'); }
        if ($request->has('area_of_specialization')) { $updateData['area_of_specialization'] = $request->input('area_of_specialization'); }
        if ($request->has('sponsorship_type')) { $updateData['sponsorship_type'] = $request->input('sponsorship_type'); }
        if ($request->has('sponsor_name')) { $updateData['sponsor_name'] = $request->input('sponsor_name'); }
        if ($request->has('previous_institution')) { $updateData['previous_institution'] = $request->input('previous_institution'); }
        if ($request->has('previous_qualification')) { $updateData['previous_qualification'] = $request->input('previous_qualification'); }
        if ($request->has('previous_course_of_study')) { $updateData['previous_course_of_study'] = $request->input('previous_course_of_study'); }
        if ($request->has('previous_class_of_degree')) { $updateData['previous_class_of_degree'] = $request->input('previous_class_of_degree'); }
        if ($request->has('previous_cgpa')) { $updateData['previous_cgpa'] = $request->filled('previous_cgpa') ? (float) $request->input('previous_cgpa') : null; }
        if ($request->has('graduation_year')) { $updateData['graduation_year'] = $request->filled('graduation_year') ? (int) $request->input('graduation_year') : null; }
        if ($request->has('nysc_status')) { $updateData['nysc_status'] = $request->input('nysc_status'); }
        if ($request->has('nysc_certificate_number')) { $updateData['nysc_certificate_number'] = $request->input('nysc_certificate_number'); }
        if ($request->has('statement_of_purpose')) { $updateData['statement_of_purpose'] = $request->input('statement_of_purpose'); }
        if ($request->has('research_proposal_title')) { $updateData['research_proposal_title'] = $request->input('research_proposal_title'); }
        if ($request->has('work_experience_years')) { $updateData['work_experience_years'] = (int) $request->input('work_experience_years', 0); }
        if ($request->has('official_transcript_status')) { $updateData['official_transcript_status'] = $request->input('official_transcript_status'); }

        if ($applicant->status === 'pending_payment' && $applicant->hasPaidApplicationFee()) {
            $updateData['status'] = 'draft';
        }

        if (! empty($updateData)) {
            $applicant->update($updateData);
        }

        $pgService = app(\App\Services\Admissions\PostgraduateAdmissionService::class);

        // Child relations: qualifications
        if ($request->has('qualifications') && is_array($request->input('qualifications'))) {
            $rawQualifications = (array) $request->input('qualifications');
            $normalized = [];
            foreach ($rawQualifications as $qItem) {
                if (empty($qItem['institution_name']) && empty($qItem['qualification_type'])) {
                    continue;
                }
                $qualType = trim((string) ($qItem['qualification_type'] ?? ''));
                if (strcasecmp($qualType, 'Other') === 0 && ! empty($qItem['other_qualification'])) {
                    $qualType = trim((string) $qItem['other_qualification']);
                }
                $qItem['qualification_type'] = $qualType;
                $normalized[] = $qItem;
            }
            if (! empty($normalized)) {
                $pgService->syncQualifications($applicant, $normalized);
            }
        }

        // Child relations: employments
        if ($request->has('employments') && is_array($request->input('employments'))) {
            $validEmployments = array_filter(
                (array) $request->input('employments'),
                fn ($e) => ! empty($e['employer_name']) || ! empty($e['job_title'])
            );
            $pgService->syncEmployments($applicant, array_values($validEmployments));
        }

        // Child relations: referees
        if ($request->has('referees') && is_array($request->input('referees'))) {
            $validReferees = array_filter(
                (array) $request->input('referees'),
                fn ($r) => ! empty($r['name']) && ! empty($r['email'])
            );
            if (! empty($validReferees)) {
                $pgService->syncReferees($applicant, array_values($validReferees));
            }
        }

        // File uploads (if files were uploaded on this step)
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

        $applicant->load(['documents', 'qualifications', 'referees', 'employments']);

        return response()->json([
            'success' => true,
            'message' => 'Step saved successfully.',
            'applicant' => $applicant,
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
                    'qualifications.*.other_qualification' => 'nullable|string|max:100',
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
                    $rawQualifications = (array) $request->input('qualifications', []);
                    $normalizedQualifications = [];
                    foreach ($rawQualifications as $qItem) {
                        $qualType = trim((string) ($qItem['qualification_type'] ?? ''));
                        if (strcasecmp($qualType, 'Other') === 0 && !empty($qItem['other_qualification'])) {
                            $qualType = trim((string) $qItem['other_qualification']);
                        }
                        $qItem['qualification_type'] = $qualType;
                        $normalizedQualifications[] = $qItem;
                    }
                } else {
                    $prevQual = (string) $request->input('previous_qualification');
                    if (strcasecmp($prevQual, 'Other') === 0 && $request->filled('other_qualification')) {
                        $prevQual = trim((string) $request->input('other_qualification'));
                    }
                    $normalizedQualifications = [[
                        'qualification_type' => $prevQual,
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

            $applicant = Applicant::where('user_id', $user->id)->first();
            if (! $applicant || ! $applicant->hasPaidApplicationFee()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'programme_id' => 'Please complete your application fee payment before submitting your application.',
                ]);
            }

            $defaultJambOrRef = $isPg
                ? ('PG-' . strtoupper(uniqid()))
                : ('PENDING-' . time());

            $primaryQual = $normalizedQualifications[0] ?? [];

            $submittedJamb = $request->input('jamb_number') ?: $request->input('jamb_registration_number');
            $finalJamb = $submittedJamb ? trim((string) $submittedJamb) : ($applicant->jamb_registration_number ?: $defaultJambOrRef);

            $applicant->update([
                'jamb_registration_number' => $finalJamb,
                'application_mode' => $isPg ? 'PG' : ($request->input('mode') ?: $applicant->application_mode),
                'program_choice_1' => $request->input('programme_id') ?: $applicant->program_choice_1,
                'status' => 'submitted',
                'application_number' => $applicant->application_number ?: \App\Helpers\ApplicationNumberHelper::generate(),

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
            ]);

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

            try {
                $user->notify(new \App\Notifications\ApplicationSubmitted($applicant));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed sending application submitted notification: ' . $e->getMessage());
            }

            return redirect()
                ->route('applicant.apply.show')
                ->with('success', 'Your application has been successfully submitted and is currently under review.');
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
            ->with(['programme.department.faculty', 'state', 'lga', 'documents', 'referees', 'qualifications', 'employments'])
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
