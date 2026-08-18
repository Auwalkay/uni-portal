<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Bulletin;
use App\Models\CourseRegistration;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Models\Subject;
use App\Models\SystemSetting;
use App\Models\Timetable;
use App\Services\AcademicCacheService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function dashboard()
    {
        $student = Student::where('user_id', auth()->id())->with('user')->first();

        // Simple check: if key fields are present
        $isProfileComplete = $student && $student->phone_number && $student->address && $student->next_of_kin_name;

        // Calculate registered units for current session
        $currentSession = Session::current();
        $currentSemester = Semester::current();

        $currentStudentSession = null;
        if ($student) {
            $currentStudentSession = StudentSession::where('student_id', $student->id)
                ->where('status', 'active')
                ->latest()
                ->first();
        }

        // Fallback to active global session if student has no specific active student session record
        $resolvedSession = $currentStudentSession?->session ?? $currentSession;
        
        if ($currentStudentSession && $currentSession && $resolvedSession->id === $currentSession->id) {
            $resolvedSemester = $currentSemester;
        } else {
            $resolvedSemester = $currentStudentSession?->semester
                ? Semester::where('session_id', $resolvedSession?->id)->where('name', $currentStudentSession->semester)->first()
                : $currentSemester;
        }

        // Check for pending promotion
        $pendingSessionName = null;
        if ($student && $student->pending_promotion_session_id) {
            $pendingSessionName = Session::where('id', $student->pending_promotion_session_id)->value('name');
        }

        // Stats Calculations
        $cgpa = '0.00';
        if ($student) {
            $enforceSchoolFee = filter_var(SystemSetting::get('enforce_school_fee_for_results', false), FILTER_VALIDATE_BOOLEAN);
            $enforceHostelFee = filter_var(SystemSetting::get('enforce_hostel_fee_for_results', false), FILTER_VALIDATE_BOOLEAN);

            $allRegs = CourseRegistration::where('student_id', $student->id)
                ->where('is_published', true)
                ->with(['course', 'semester'])
                ->get();

            $cgpaRegs = $allRegs->filter(function ($reg) use ($enforceSchoolFee, $enforceHostelFee, $student) {
                $semesterName = $reg->semester?->name ?? '';
                $isSecondSem = stripos($semesterName, 'Second') !== false || strpos($semesterName, '2') !== false;

                if (! $isSecondSem) {
                    return true; // First Sem is never blocked
                }

                // For Second Sem, check clearance in the registration's session
                $schoolFeeCleared = true;
                if ($enforceSchoolFee) {
                    $schoolFeeInvoice = \App\Models\Invoice::where('user_id', auth()->id())
                        ->where('type', 'school_fee')
                        ->where('session_id', $reg->session_id)
                        ->first();
                    $schoolFeeCleared = $schoolFeeInvoice && $schoolFeeInvoice->status === 'paid';
                }

                $hostelFeeCleared = true;
                if ($enforceHostelFee) {
                    $hostelBooking = \App\Models\HostelBooking::where('student_id', $student->id)
                        ->where('session_id', $reg->session_id)
                        ->first();
                    if ($hostelBooking) {
                        $hostelInvoice = $hostelBooking->invoice;
                        $hostelFeeCleared = $hostelInvoice && $hostelInvoice->status === 'paid';
                    }
                }

                return $schoolFeeCleared && $hostelFeeCleared;
            });

            $cgpa = number_format($cgpaRegs->isEmpty() ? 0 : app(GradingService::class)->calculateGPA($cgpaRegs), 2);
        }
        $totalUnits = 0;
        // Ensure level doesn't 'go down' when viewing historical sessions
        $level = max((int) ($student->current_level ?? 0), (int) ($currentStudentSession->level ?? 0));
        $academicStatus = 'Good Standing';
        $showRegistrationNotification = false;
        $registrationMessage = '';
        $isRegistrationActive = false;

        if ($student && $resolvedSession && $currentStudentSession) {
            $totalUnits = CourseRegistration::where('student_session_id', $currentStudentSession->id)
                ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
                ->sum('courses.units');
        }

        if ($student && $resolvedSession) {
            // Check for Registration Notification
            if ($resolvedSemester) {
                $now = now();
                $start = $resolvedSemester->registration_starts_at;
                $end = $resolvedSemester->registration_ends_at;

                $isOpen = (bool) $resolvedSession->registration_enabled;
                if ($start && $now->lt($start)) {
                    $isOpen = false;
                }
                if ($end && $now->gt($end)) {
                    $isOpen = false;
                }

                if ($isOpen) {
                    $isRegistrationActive = true;
                    // Check if already registered
                    $hasRegistered = false;
                    if ($currentStudentSession) {
                        $hasRegistered = CourseRegistration::where('student_session_id', $currentStudentSession->id)
                            ->where('semester_id', $resolvedSemester->id)
                            ->exists();
                    }

                    if (! $hasRegistered) {
                        $showRegistrationNotification = true;
                        if ($end) {
                            $registrationMessage = "Registration for {$resolvedSemester->name} closes on ".$end->format('M d, Y').'. Register now to avoid penalties.';
                        } else {
                            $registrationMessage = "Registration for {$resolvedSemester->name} is now open.";
                        }
                    }
                } else {
                    $showRegistrationNotification = true;
                    $isRegistrationActive = false;
                    if ($end && $now->gt($end)) {
                        $registrationMessage = "Course registration for {$resolvedSemester->name} is currently closed. The deadline was ".$end->format('M d, Y').'.';
                    } elseif ($start && $now->lt($start)) {
                        $registrationMessage = "Course registration for {$resolvedSemester->name} is not active yet. It is scheduled to open on ".$start->format('M d, Y').'.';
                    } else {
                        $registrationMessage = "Course registration for {$resolvedSemester->name} is currently closed.";
                    }
                }
            }
        }

        $showHostelNotification = false;
        $hostelNotificationMessage = '';
        $bookingEnabled = filter_var(SystemSetting::get('enable_hostel_booking', true), FILTER_VALIDATE_BOOLEAN);

        if ($bookingEnabled && $student && $resolvedSession) {
            $hasBooked = \App\Models\HostelBooking::where('student_id', $student->id)
                ->where('session_id', $resolvedSession->id)
                ->exists();

            if (! $hasBooked) {
                $showHostelNotification = true;
                $hostelNotificationMessage = 'Hostel booking is now open for the current session. Select your room to reserve your accommodation.';
            }
        }

        // Fetch Timetable for Registered Courses
        $timetable = [];
        if ($student && $resolvedSession && $currentStudentSession) {
            $timetable = AcademicCacheService::getStudentTimetable($student->id, $currentStudentSession->session_id);
        }

        // Check School Fee status for CURRENT session
        $schoolFeeStatus = 'unpaid';
        if ($resolvedSession) {
            $schoolFeeInvoice = Invoice::where('user_id', auth()->id())
                ->where('type', 'school_fee')
                ->where('session_id', $resolvedSession->id)
                ->first();

            if ($schoolFeeInvoice) {
                $schoolFeeStatus = $schoolFeeInvoice->status;
            }
        }

        // Fetch latest bulletins/announcements for student dashboard
        $announcements = Cache::remember('student_dashboard_bulletins', 60 * 10, function () {
            return Bulletin::with('author')
                ->whereIn('target_audience', ['all', 'students'])
                ->orderBy('is_pinned', 'desc')
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->get();
        });

        return Inertia::render('Student/Dashboard', [
            'student' => $student->load(['program']),
            'user' => $student ? $student->user : auth()->user(),
            'isProfileComplete' => $isProfileComplete,
            'schoolFeeStatus' => $schoolFeeStatus,
            'showRegistrationNotification' => $showRegistrationNotification,
            'registrationMessage' => $registrationMessage,
            'isRegistrationActive' => $isRegistrationActive,
            'showHostelNotification' => $showHostelNotification,
            'hostelNotificationMessage' => $hostelNotificationMessage,
            'pendingSession' => $pendingSessionName,
            'announcements' => $announcements,
            'stats' => [
                'cgpa' => $cgpa,
                'totalUnits' => $totalUnits,
                'level' => $level,
                'status' => $academicStatus,
                'session' => $resolvedSession->name ?? $student->admittedSession?->name ?? 'N/A',
                'semester' => $resolvedSemester->name ?? 'N/A',
            ],
            'timetable' => $timetable,
            'activeSession' => $resolvedSession ? [
                'id' => $resolvedSession->id,
                'name' => $resolvedSession->name,
                'school_fee_payment_enabled' => (bool)$resolvedSession->school_fee_payment_enabled,
                'late_payment_deadline' => $resolvedSession->late_payment_deadline ? $resolvedSession->late_payment_deadline->toIso8601String() : null,
                'late_fee_amount' => (float)$resolvedSession->late_fee_amount,
            ] : null,
        ]);
    }

    public function edit(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->with(['user', 'state', 'lga', 'oLevelResults'])
            ->firstOrFail();

        $states = AcademicCacheService::getStates();

        $allSubjects = Cache::remember('all_subjects', 60 * 60 * 24, function () {
            return Subject::orderBy('name')->get();
        });

        // Determine which fields are editable (can only set them if they are null/empty)
        $canEditGender = is_null($student->gender) || $student->gender === '';
        $canEditState = is_null($student->state_id);
        $canEditLga = is_null($student->lga_id);
        $canEditJamb = is_null($student->jamb_registration_number) || $student->jamb_registration_number === '';
        $canEditOlevel = ! $student->oLevelResults()->exists();
        $canEditIndigene = is_null($student->indigene_letter_path);

        return Inertia::render('Student/Profile/Edit', [
            'student' => $student,
            'user' => $request->user(),
            'states' => $states,
            'allSubjects' => $allSubjects,
            'canEditGender' => $canEditGender,
            'canEditState' => $canEditState,
            'canEditLga' => $canEditLga,
            'canEditJamb' => $canEditJamb,
            'canEditOlevel' => $canEditOlevel,
            'canEditIndigene' => $canEditIndigene,
            'status' => session('status'),
            'warning' => session('warning'),
        ]);
    }

    public function update(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();

        // Determine which fields are editable (can only set them if they are null/empty)
        $canEditGender = is_null($student->gender) || $student->gender === '';
        $canEditState = is_null($student->state_id);
        $canEditLga = is_null($student->lga_id);
        $canEditJamb = is_null($student->jamb_registration_number) || $student->jamb_registration_number === '';
        $canEditOlevel = ! $student->oLevelResults()->exists();
        $canEditIndigene = is_null($student->indigene_letter_path);

        // Base rules (always editable)
        $rules = [
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'next_of_kin_name' => 'required|string',
            'next_of_kin_phone' => 'required|string',
            'next_of_kin_address' => 'nullable|string',
            'passport_photograph' => is_null($student->passport_photo_path) ? 'required|image|max:500' : 'nullable|image|max:500',
        ];

        // Conditional validation rules based on whether they were null
        if ($canEditGender) {
            $rules['gender'] = 'required|string|in:male,female';
        }
        if ($canEditState) {
            $rules['state_id'] = 'required|exists:states,id';
        }
        if ($canEditLga) {
            $rules['lga_id'] = 'required|exists:lgas,id';
        }
        if ($canEditJamb) {
            // $rules['jamb_registration_number'] = 'required|string|max:255|unique:students,jamb_registration_number,' . $student->id;
            $rules['jamb_registration_number'] = 'nullable|string|max:255';

        }
        if ($canEditIndigene) {
            $rules['indigene_letter'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:500';
        }
        if ($canEditOlevel) {
            $rules['o_level_sittings'] = 'required|array|min:1|max:2';
            $rules['o_level_sittings.*.exam_type'] = 'required|string';
            $rules['o_level_sittings.*.exam_year'] = 'required|string';
            $rules['o_level_sittings.*.exam_number'] = 'required|string';
            $rules['o_level_sittings.*.subjects'] = 'required|array|min:1';
            $rules['o_level_sittings.*.scanned_copy'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:500';
        }

        $validated = $request->validate($rules);

        $data = [
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'next_of_kin_name' => $request->next_of_kin_name,
            'next_of_kin_phone' => $request->next_of_kin_phone,
            'next_of_kin_address' => $request->input('next_of_kin_address'),
        ];

        if ($canEditGender && $request->has('gender')) {
            $data['gender'] = strtolower($request->gender);
        }
        if ($canEditState && $request->has('state_id')) {
            $data['state_id'] = $request->state_id;
        }
        if ($canEditLga && $request->has('lga_id')) {
            $data['lga_id'] = $request->lga_id;
        }
        if ($canEditJamb && $request->has('jamb_registration_number')) {
            $data['jamb_registration_number'] = strtoupper(trim($request->jamb_registration_number));
        }

        if ($request->hasFile('passport_photograph')) {
            $path = $request->file('passport_photograph')->store('profile-photos', 'public');
            $data['passport_photo_path'] = $path;
        }

        if ($canEditIndigene && $request->hasFile('indigene_letter')) {
            $path = $request->file('indigene_letter')->store('documents/indigene', 'public');
            $data['indigene_letter_path'] = $path;
        }

        $student->update($data);

        // Sync O-Level Results (Only if not already set)
        if ($canEditOlevel && $request->filled('o_level_sittings')) {
            foreach ($request->o_level_sittings as $index => $sitting) {
                if (empty($sitting['exam_type'])) {
                    continue;
                }

                $oLevelData = [
                    'exam_type' => $sitting['exam_type'],
                    'exam_year' => $sitting['exam_year'],
                    'exam_number' => $sitting['exam_number'],
                    'subjects' => $sitting['subjects'] ?? [],
                ];

                if ($request->hasFile("o_level_sittings.{$index}.scanned_copy")) {
                    $path = $request->file("o_level_sittings.{$index}.scanned_copy")->store('documents/olevel', 'public');
                    $oLevelData['scanned_copy_path'] = $path;
                }

                $student->oLevelResults()->create($oLevelData);
            }
        }

        return redirect()->route('student.dashboard')->with('status', 'Profile updated successfully.');
    }

    public function downloadAdmissionLetter()
    {
        $user = auth()->user();
        $applicant = Applicant::where('user_id', $user->id)->with(['scholarship'])->first();
        $student = Student::where('user_id', $user->id)->with(['user', 'state', 'lga', 'program.department.faculty', 'admittedSession', 'scholarship'])->first();

        if (! $applicant && ! $student) {
            return back()->with('error', 'Admission record not found.');
        }

        // If we only have an applicant, check status
        if ($applicant && ! $student && ! in_array($applicant->status, ['admitted', 'enrolled'])) {
            return back()->with('error', 'Admission letter is not available.');
        }

        $identifer = $student->matriculation_number ?? $applicant->jamb_registration_number ?? $applicant->application_number ?? 'Letter';
        $fileName = "Admission_Letter_{$identifer}.pdf";
        $filePath = "admission_letters/{$user->id}.pdf";

        // if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
        //     $cacheModifiedTime = \Illuminate\Support\Facades\Storage::disk('local')->lastModified($filePath);
        //     $studentUpdatedTime = $student ? $student->updated_at->timestamp : 0;
        //     $applicantUpdatedTime = $applicant ? $applicant->updated_at->timestamp : 0;
        //     $scholarshipUpdatedTime = ($student && $student->scholarship) ? $student->scholarship->updated_at->timestamp : (($applicant && $applicant->scholarship) ? $applicant->scholarship->updated_at->timestamp : 0);

        //     if ($cacheModifiedTime >= max($studentUpdatedTime, $applicantUpdatedTime, $scholarshipUpdatedTime)) {
        //         return \Illuminate\Support\Facades\Storage::disk('local')->download($filePath, $fileName, [
        //             'Content-Type' => 'application/pdf',
        //             'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        //         ]);
        //     }
        // }

        // Prepare data for the letter
        if ($student) {
            // Calculate Fees for the Letter
            $feesData = $this->calculateEstimatedFees($student);

            // Calculate student's entry level (their first session level or fallback)
            $entryLevel = (int) ($student->sessions()->orderBy('created_at', 'asc')->value('level')
                ?? $student->sessions()->orderBy('level', 'asc')->value('level')
                ?? $student->current_level
                ?? 100);

            // Map student data to what the template expects
            $data = [
                'applicant' => (object) [
                    'id' => $student->id,
                    'user' => $student->user,
                    'first_name' => explode(' ', $student->user->name)[0],
                    'address' => $student->address,
                    'state' => $student->state,
                    'lga' => $student->lga,
                    'application_number' => $student->matriculation_number,
                    'jamb_registration_number' => $student->jamb_registration_number,
                    'application_mode' => $student->entry_mode ?? 'UTME',
                    'programme' => $student->program,
                    'entry_level' => $entryLevel,
                    'is_student' => true,
                ],
                'faculty_name' => $student->faculty?->name ?? $student->program?->department?->faculty?->name ?? 'N/A',
                'programme_name' => $student->program?->name ?? 'N/A',
                'session_name' => $student->admittedSession?->name ?? \App\Models\Session::current()->name ?? '2025/2026',
                'fees' => $feesData,
            ];
        } else {
            $applicant->load(['user', 'programme.department.faculty', 'state', 'lga', 'scholarship']);

            // For applicants, we use their first program choice and current session fees
            $currentSession = \App\Models\Session::current();
            $feesData = $this->calculateEstimatedFeesForApplicant($applicant, $currentSession);

            $data = [
                'applicant' => $applicant,
                'faculty_name' => $applicant->programme?->department->faculty->name ?? 'N/A',
                'programme_name' => $applicant->programme?->name ?? 'N/A',
                'session_name' => $currentSession->name ?? '2025/2026',
                'fees' => $feesData,
            ];
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.admission_letter', $data)
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
            ]);

        Storage::disk('local')->put($filePath, $pdf->output());

        return $pdf->download($fileName);
    }

    private function calculateEstimatedFees($student)
    {
        $sessionId = $student->admitted_session_id ?? \App\Models\Session::current()?->id;
        if (! $sessionId) {
            return null;
        }

        $allConfigs = \App\Models\FeeConfiguration::where('session_id', $sessionId)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)->orWhereNull('level');
            })
            ->where(function ($q) use ($student) {
                $q->where('entry_mode', $student->entry_mode)->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->with('feeType')
            ->get();

        $tuition = 0;
        $discountTuitionBase = 0;
        $oneTimeFeesTotal = 0;
        $oneTimeFeesList = [];

        $grouped = $allConfigs->groupBy('fee_type_id');
        foreach ($grouped as $feeTypeId => $configs) {
            $resolved = $configs->where('program_id', $student->program_id)->first()
                ?? $configs->where('department_id', $student->department_id)->whereNull('program_id')->first()
                ?? $configs->where('faculty_id', $student->faculty_id)->whereNull('department_id')->whereNull('program_id')->first()
                ?? $configs->whereNull('faculty_id')->whereNull('department_id')->whereNull('program_id')->first();

            if ($resolved) {
                if ($resolved->feeType && $resolved->feeType->is_one_time) {
                    $oneTimeFeesTotal += $resolved->amount;
                    $oneTimeFeesList[] = [
                        'name' => $resolved->feeType->name,
                        'amount' => $resolved->amount,
                    ];
                } else {
                    $tuition += $resolved->amount;
                    $feeName = $resolved->feeType ? strtolower($resolved->feeType->name) : '';
                    $feeSlug = $resolved->feeType ? $resolved->feeType->slug : '';
                    $isExcluded = str_contains($feeName, 'drug test') ||
                                  str_contains($feeSlug, 'drug-test') ||
                                  str_contains($feeName, 'acceptance') ||
                                  str_contains($feeSlug, 'acceptance') ||
                                  str_contains($feeName, 'matriculation') ||
                                  str_contains($feeSlug, 'matriculation');

                    if (! $isExcluded) {
                        $discountTuitionBase += $resolved->amount;
                    }
                }
            }
        }

        $adminCharge = SystemSetting::get('admin_charge_enabled', true)
            ? SystemSetting::get('admin_charge_amount', 250000) : 0;

        // Calculate Discount based on Scholarship Coverage
        $discount = 0;
        $scholarship = $student->scholarship;
        if ($scholarship && ($student->program?->scholarship_eligible ?? true)) {
            $baseForDiscount = $discountTuitionBase;
            if ($adminCharge > 0 && $scholarship->covers_admin_charges) {
                $baseForDiscount += $adminCharge;
            }
            if ($scholarship->type === 'fixed') {
                $discount = max(0, $baseForDiscount - $scholarship->amount);
            } else {
                $discount = $baseForDiscount * ($scholarship->percentage / 100);
            }
        }

        $total = $tuition + $adminCharge + $oneTimeFeesTotal;

        return [
            'tuition' => $tuition,
            'admin_charge' => $adminCharge,
            'one_time_fees' => $oneTimeFeesTotal,
            'one_time_fees_list' => $oneTimeFeesList,
            'discount' => $discount,
            'total' => $total - $discount,
            'scholarship_name' => $scholarship?->name,
        ];
    }

    private function calculateEstimatedFeesForApplicant($applicant, $session)
    {
        if (! $session) {
            return null;
        }

        $program = $applicant->programme;
        $deptId = $program?->department_id;
        $facultyId = $program?->department?->faculty_id;

        $entryMode = $applicant->application_mode;
        if ($entryMode === 'DE') {
            $entryMode = 'Direct Entry';
        } elseif ($entryMode === 'PG') {
            $entryMode = 'Postgraduate';
        }

        $allConfigs = \App\Models\FeeConfiguration::where('session_id', $session->id)
            ->where(function ($q) use ($entryMode) {
                $q->where(function ($sub) {
                    $sub->where('level', '100')->orWhereNull('level');
                })
                    ->orWhere('entry_mode', $entryMode);
            })
            ->where(function ($q) use ($entryMode) {
                $q->where('entry_mode', $entryMode)->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->with('feeType')
            ->get();

        $tuition = 0;
        $discountTuitionBase = 0;
        $oneTimeFeesTotal = 0;
        $oneTimeFeesList = [];

        $grouped = $allConfigs->groupBy('fee_type_id');
        foreach ($grouped as $feeTypeId => $configs) {
            $resolved = $configs->where('program_id', $applicant->program_choice_1)->first()
                ?? $configs->where('department_id', $deptId)->whereNull('program_id')->first()
                ?? $configs->where('faculty_id', $facultyId)->whereNull('department_id')->whereNull('program_id')->first()
                ?? $configs->whereNull('faculty_id')->whereNull('department_id')->whereNull('program_id')->first();

            if ($resolved) {
                if ($resolved->feeType && $resolved->feeType->is_one_time) {
                    $oneTimeFeesTotal += $resolved->amount;
                    $oneTimeFeesList[] = [
                        'name' => $resolved->feeType->name,
                        'amount' => $resolved->amount,
                    ];
                } else {
                    $tuition += $resolved->amount;
                    $feeName = $resolved->feeType ? strtolower($resolved->feeType->name) : '';
                    $feeSlug = $resolved->feeType ? $resolved->feeType->slug : '';
                    $isExcluded = str_contains($feeName, 'drug test') ||
                                  str_contains($feeSlug, 'drug-test') ||
                                  str_contains($feeName, 'acceptance') ||
                                  str_contains($feeSlug, 'acceptance') ||
                                  str_contains($feeName, 'matriculation') ||
                                  str_contains($feeSlug, 'matriculation');

                    if (! $isExcluded) {
                        $discountTuitionBase += $resolved->amount;
                    }
                }
            }
        }

        $adminCharge = SystemSetting::get('admin_charge_enabled', true)
            ? SystemSetting::get('admin_charge_amount', 250000) : 0;

        // Calculate Discount based on Scholarship Coverage
        $discount = 0;
        $scholarship = $applicant->scholarship;
        if ($scholarship && ($applicant->programme?->scholarship_eligible ?? true)) {
            $baseForDiscount = $discountTuitionBase;
            if ($adminCharge > 0 && $scholarship->covers_admin_charges) {
                $baseForDiscount += $adminCharge;
            }
            if ($scholarship->type === 'fixed') {
                $discount = max(0, $baseForDiscount - $scholarship->amount);
            } else {
                $discount = $baseForDiscount * ($scholarship->percentage / 100);
            }
        }

        $total = $tuition + $adminCharge + $oneTimeFeesTotal;

        return [
            'tuition' => $tuition,
            'admin_charge' => $adminCharge,
            'one_time_fees' => $oneTimeFeesTotal,
            'one_time_fees_list' => $oneTimeFeesList,
            'discount' => $discount,
            'total' => $total - $discount,
            'scholarship_name' => $scholarship?->name,
        ];
    }

    public function manual()
    {
        return Inertia::render('Student/Manual/Index');
    }
}
