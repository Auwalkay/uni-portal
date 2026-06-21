<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Models\Timetable;
use Illuminate\Http\Request;
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
        if ($student && $currentSession) {
            $currentStudentSession = StudentSession::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->first();
        }

        // Stats Calculations
        $cgpa = '0.00';
        if ($student) {
            $enforceSchoolFee = filter_var(\App\Models\SystemSetting::get('enforce_school_fee_for_results', false), FILTER_VALIDATE_BOOLEAN);
            $enforceHostelFee = filter_var(\App\Models\SystemSetting::get('enforce_hostel_fee_for_results', false), FILTER_VALIDATE_BOOLEAN);

            $allRegs = CourseRegistration::where('student_id', $student->id)
                ->where('is_published', true)
                ->with(['course', 'semester'])
                ->get();

            $cgpaRegs = $allRegs->filter(function ($reg) use ($enforceSchoolFee, $enforceHostelFee, $student) {
                $semesterName = $reg->semester?->name ?? '';
                $isSecondSem = stripos($semesterName, 'Second') !== false || strpos($semesterName, '2') !== false;
                
                if (!$isSecondSem) {
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

            $cgpa = number_format($cgpaRegs->isEmpty() ? 0 : app(\App\Services\GradingService::class)->calculateGPA($cgpaRegs), 2);
        }
        $totalUnits = 0;
        // Ensure level doesn't 'go down' when viewing historical sessions
        $level = max((int)($student->current_level ?? 0), (int)($currentStudentSession->level ?? 0));
        $academicStatus = 'Good Standing';
        $showRegistrationNotification = false;
        $registrationMessage = '';

        if ($student && $currentSession && $currentStudentSession) {
            // Assuming we have a relation or through model.
            // Let's use CourseRegistration model directly for now.
            $totalUnits = CourseRegistration::where('student_session_id', $currentStudentSession->id)
                ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
                ->sum('courses.units');

            // Check for Registration Notification
            if ($currentSemester && $currentSession->registration_enabled) {
                $now = now();
                $start = $currentSemester->registration_starts_at;
                $end = $currentSemester->registration_ends_at;

                $isOpen = true;
                if ($start && $now->lt($start)) {
                    $isOpen = false;
                }
                if ($end && $now->gt($end)) {
                    $isOpen = false;
                }

                if ($isOpen) {
                    // Check if already registered
                    $hasRegistered = CourseRegistration::where('student_session_id', $currentStudentSession->id)
                        ->where('semester_id', $currentSemester->id)
                        ->exists();

                    if (! $hasRegistered) {
                        $showRegistrationNotification = true;
                        if ($end) {
                            $registrationMessage = "Registration for {$currentSemester->name} closes on ".$end->format('M d, Y').'. Register now to avoid penalties.';
                        } else {
                            $registrationMessage = "Registration for {$currentSemester->name} is now open.";
                        }
                    }
                }
            }
        }

        // Fetch Timetable for Registered Courses
        $timetable = [];
        if ($student && $currentSession && $currentStudentSession) {
            $timetable = \App\Services\AcademicCacheService::getStudentTimetable($student->id, $currentStudentSession->session_id);
        }

        // Check School Fee status for CURRENT session
        $schoolFeeStatus = 'unpaid';
        if ($currentSession && $currentStudentSession) {
            $schoolFeeInvoice = Invoice::where('user_id', auth()->id())
                ->where('type', 'school_fee')
                ->where('student_session_id', $currentStudentSession->id)
                ->first();
            
            if ($schoolFeeInvoice) {
                $schoolFeeStatus = $schoolFeeInvoice->status;
            }
        }

        return Inertia::render('Student/Dashboard', [
            'student' => $student->load(['program']),
            'user' => $student ? $student->user : auth()->user(),
            'isProfileComplete' => $isProfileComplete,
            'schoolFeeStatus' => $schoolFeeStatus,
            'showRegistrationNotification' => $showRegistrationNotification,
            'registrationMessage' => $registrationMessage,
            'stats' => [
                'cgpa' => $cgpa,
                'totalUnits' => $totalUnits,
                'level' => $level,
                'status' => $academicStatus,
                'session' => $currentSession->name ?? 'N/A',
                'semester' => $currentSemester->name ?? 'N/A',
            ],
            'timetable' => $timetable,
        ]);
    }

    public function edit(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)
            ->with(['user', 'state', 'lga', 'oLevelResults'])
            ->firstOrFail();

        $states = \App\Services\AcademicCacheService::getStates();

        $allSubjects = \Illuminate\Support\Facades\Cache::remember('all_subjects', 60 * 60 * 24, function () {
            return \App\Models\Subject::orderBy('name')->get();
        });

        $currentSession = Session::current();
        // Allow full edit if in admitted session (fresh) OR if no admitted session set (legacy/error case handling)
        $canEditProfile = $currentSession && $student->admitted_session_id === $currentSession->id;

        return \Inertia\Inertia::render('Student/Profile/Edit', [
            'student' => $student,
            'user' => $request->user(),
            'states' => $states,
            'allSubjects' => $allSubjects,
            'canEditProfile' => $canEditProfile,
            'status' => session('status'),
        ]);
    }

    public function update(Request $request)
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $currentSession = Session::current();
        $canEditProfile = $currentSession && $student->admitted_session_id === $currentSession->id;

        // Base rules (always editable)
        $rules = [
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'state_id' => 'required|exists:states,id', // Address related? Usually Origin. Wait. "Origin & Docs" was step 2.
            // Requirement: "others can only preview or change address and picture"
            // "Address" usually means residential address.
            // "Structure": Personal(Phone, Address), Origin(State, LGA, Indigene), O-level, NoK.
            // So for returning: Phone, Address, Passport, Next of Kin.
            // State/LGA are usually Origin (Immutable).

            'next_of_kin_name' => 'required|string',
            'next_of_kin_phone' => 'required|string',
            'next_of_kin_address' => 'nullable|string',
            'passport_photograph' => 'nullable|image|max:2048',
        ];

        // Full editing rules
        if ($canEditProfile) {
            $rules = array_merge($rules, [
                'gender' => 'required|string',
                'dob' => 'required|date',
                'state_id' => 'required|exists:states,id', // Origin
                'lga_id' => 'required|exists:lgas,id',
                'indigene_letter' => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
                'o_level_sittings' => 'nullable|array|max:2',
                'o_level_sittings.*.id' => 'nullable|integer',
                'o_level_sittings.*.exam_type' => 'nullable|string',
                'o_level_sittings.*.exam_year' => 'nullable|string',
                'o_level_sittings.*.exam_number' => 'nullable|string',
                'o_level_sittings.*.subjects' => 'nullable|array',
                'o_level_sittings.*.scanned_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);
        }

        $validated = $request->validate($rules);

        $data = [
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'next_of_kin_name' => $request->next_of_kin_name,
            'next_of_kin_phone' => $request->next_of_kin_phone,
            'next_of_kin_address' => $request->input('next_of_kin_address'),
        ];

        if ($canEditProfile) {
            $data['gender'] = strtolower($request->gender);
            $data['dob'] = $request->dob;
            $data['state_id'] = $request->state_id;
            $data['lga_id'] = $request->lga_id;
        }

        if ($request->hasFile('passport_photograph')) {
            $path = $request->file('passport_photograph')->store('profile-photos', 'public');
            $data['passport_photo_path'] = $path;
        }

        if ($canEditProfile && $request->hasFile('indigene_letter')) {
            $path = $request->file('indigene_letter')->store('documents/indigene', 'public');
            $data['indigene_letter_path'] = $path;
        }

        $student->update($data);

        // Sync O-Level Results (Only for Fresh Students)
        $processedIds = [];
        if ($canEditProfile && $request->filled('o_level_sittings')) {
            foreach ($request->o_level_sittings as $index => $sitting) {
                // Skip empty rows if needed, mainly checking exam_type
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

                if (! empty($sitting['id'])) {
                    // Update existing
                    $result = $student->oLevelResults()->find($sitting['id']);
                    if ($result) {
                        $result->update($oLevelData);
                        $processedIds[] = $result->id;
                    }
                } else {
                    // Create new
                    $result = $student->oLevelResults()->create($oLevelData);
                    $processedIds[] = $result->id;
                }
            }
        }

        // Delete removed sittings (results that belong to student but were not in the submitted list)
        // If the user submitted empty array or no valid sittings, this might delete all if we intended to clear.
        // But logic above ($request->filled) keeps old ones if empty.
        // Better logic: Only delete if $request->o_level_sittings was actually sent (even empty).
        // Since frontend sends the array, we can safely delete except processed.
        // However, we should be careful. If $processedIds is empty but sittings were sent (e.g. user deleted all in UI), we delete all.
        // If sittings were NOT sent (e.g. unrelated update), we shouldn't delete.
        // But Edit form sends everything. So safe to deleteNotIn.

        if ($canEditProfile && $request->has('o_level_sittings')) {
            $student->oLevelResults()->whereNotIn('id', $processedIds)->delete();
        }

        return redirect()->route('student.profile.edit')->with('status', 'Profile updated successfully.');
    }

    public function downloadAdmissionLetter()
    {
        $user = auth()->user();
        $applicant = \App\Models\Applicant::where('user_id', $user->id)->with(['scholarship'])->first();
        $student = \App\Models\Student::where('user_id', $user->id)->with(['user', 'state', 'lga', 'program.department.faculty', 'admittedSession', 'scholarship'])->first();

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

        \Illuminate\Support\Facades\Storage::disk('local')->put($filePath, $pdf->output());

        return $pdf->download($fileName);
    }

    private function calculateEstimatedFees($student)
    {
        $sessionId = $student->admitted_session_id ?? \App\Models\Session::current()?->id;
        if (!$sessionId) return null;

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
                        'amount' => $resolved->amount
                    ];
                } else {
                    $tuition += $resolved->amount;
                    if (!($resolved->feeType && (strtolower($resolved->feeType->name) === 'drug test' || $resolved->feeType->slug === 'drug-test'))) {
                        $discountTuitionBase += $resolved->amount;
                    }
                }
            }
        }

        $adminCharge = \App\Models\SystemSetting::get('admin_charge_enabled', true) 
            ? \App\Models\SystemSetting::get('admin_charge_amount', 250000) : 0;
            
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
            'scholarship_name' => $scholarship?->name
        ];
    }

    private function calculateEstimatedFeesForApplicant($applicant, $session)
    {
        if (!$session) return null;
        
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
                        'amount' => $resolved->amount
                    ];
                } else {
                    $tuition += $resolved->amount;
                    if (!($resolved->feeType && (strtolower($resolved->feeType->name) === 'drug test' || $resolved->feeType->slug === 'drug-test'))) {
                        $discountTuitionBase += $resolved->amount;
                    }
                }
            }
        }

        $adminCharge = \App\Models\SystemSetting::get('admin_charge_enabled', true) 
            ? \App\Models\SystemSetting::get('admin_charge_amount', 250000) : 0;
            
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
            'scholarship_name' => $scholarship?->name
        ];
    }
}
