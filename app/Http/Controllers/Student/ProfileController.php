<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function dashboard()
    {
        $student = \App\Models\Student::where('user_id', auth()->id())->with('user')->first();

        // Simple check: if key fields are present
        $isProfileComplete = $student && $student->phone_number && $student->address && $student->next_of_kin_name;

        // Calculate registered units for current session
        $currentSession = \App\Models\Session::current();
        $currentSemester = \App\Models\Semester::current();

        // Stats Calculations
        $cgpa = 0.00; // Placeholder for now
        $totalUnits = 0;
        $level = $student->current_level ?? '100';
        $academicStatus = 'Good Standing';
        $showRegistrationNotification = false;
        $registrationMessage = '';

        if ($student && $currentSession) {
            // Assuming we have a relation or through model.
            // Let's use CourseRegistration model directly for now.
            $totalUnits = \App\Models\CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
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
                    $hasRegistered = \App\Models\CourseRegistration::where('student_id', $student->id)
                        ->where('session_id', $currentSession->id)
                        ->where('semester_id', $currentSemester->id)
                        ->exists();

                    if (!$hasRegistered) {
                        $showRegistrationNotification = true;
                        if ($end) {
                            $registrationMessage = "Registration for {$currentSemester->name} closes on " . $end->format('M d, Y') . '. Register now to avoid penalties.';
                        } else {
                            $registrationMessage = "Registration for {$currentSemester->name} is now open.";
                        }
                    }
                }
            }
        }

        // Fetch Timetable for Registered Courses
        $timetable = [];
        if ($student && $currentSession) {
            $registeredCourseIds = \App\Models\CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $currentSession->id)
                ->pluck('course_id');

            $timetable = \App\Models\Timetable::whereIn('course_id', $registeredCourseIds)
                ->where('session_id', $currentSession->id)
                ->with(['course'])
                ->get();
        }

        // Check School Fee status for CURRENT session
        $hasPaidSchoolFee = false;
        if ($currentSession) {
            $hasPaidSchoolFee = Invoice::where('user_id', auth()->id())
                ->where('type', 'school_fee')
                ->where('session_id', $currentSession->id)
                ->whereIn('status', ['paid'])
                ->exists();
        }

        return Inertia::render('Student/Dashboard', [
            'student' => $student->load(['program']),
            'user' => $student ? $student->user : auth()->user(),
            'isProfileComplete' => $isProfileComplete,
            'hasPaidSchoolFee' => $hasPaidSchoolFee,
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
        $student = \App\Models\Student::where('user_id', $request->user()->id)
            ->with(['user', 'state', 'lga', 'oLevelResults'])
            ->firstOrFail();

        $states = \App\Models\State::with('lgas')->orderBy('name')->get();

        $allSubjects = \Illuminate\Support\Facades\Cache::remember('all_subjects', 60 * 60 * 24, function () {
            return \App\Models\Subject::orderBy('name')->get();
        });

        $currentSession = \App\Models\Session::current();
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
        $student = \App\Models\Student::where('user_id', $request->user()->id)->firstOrFail();
        $currentSession = \App\Models\Session::current();
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
            $data['gender'] = $request->gender;
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

                if (!empty($sitting['id'])) {
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
        $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();

        if (!$applicant || $applicant->status !== 'enrolled') {
            return back()->with('error', 'Admission letter is not available.');
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
