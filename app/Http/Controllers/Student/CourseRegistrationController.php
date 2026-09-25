<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\FeeConfiguration;
use App\Models\Invoice;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Services\AcademicCacheService;
use App\Services\Finance\FeeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CourseRegistrationController extends Controller
{
    /**
     * Display student course registration history.
     */
    public function index()
    {
        $student = Student::where('user_id', Auth::id())->with('program')->firstOrFail();

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->with(['session', 'semester', 'course'])
            ->get();

        $programme = $student->program;
        $overrides = $programme
            ? DB::table('course_programme')->where('programme_id', $programme->id)->pluck('is_compulsory', 'course_id')
            : collect();

        $formattedHistory = [];
        $groupedBySession = $registrations->groupBy('session_id');

        foreach ($groupedBySession as $sessionId => $sessionRegistrations) {
            $session = $sessionRegistrations->first()->session;

            $semesters = $sessionRegistrations->groupBy('semester_id')->map(function ($semesterRegs) use ($overrides) {
                $semester = $semesterRegs->first()->semester;

                return [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'is_current' => $semester->is_current,
                    'total_units' => $semesterRegs->sum('course.units'),
                    'courses' => $semesterRegs->map(function ($r) use ($overrides) {
                        $course = $r->course;
                        if ($course) {
                            $course->is_compulsory = $overrides->has($course->id) ? (bool) $overrides->get($course->id) : false;
                        }
                        return $course;
                    }),
                ];
            })->values();

            $formattedHistory[] = [
                'id' => $session->id,
                'session' => $session->name,
                'is_current' => $session->is_current,
                'semesters' => $semesters,
            ];
        }

        $currentSession = Session::current();
        $currentSemester = Semester::current();
        $isSecondSemActive = $currentSemester && (stripos($currentSemester->name, 'Second') !== false || $currentSemester->name == '2');

        $pendingInvoicesCount = $isSecondSemActive
            ? Invoice::where('user_id', Auth::id())->where('status', '!=', 'paid')->count()
            : Invoice::where('user_id', Auth::id())->whereNotIn('status', ['paid', 'partial'])->where('paid_amount', '<=', 0)->count();

        $schoolFeeStatus = Invoice::where('user_id', Auth::id())
            ->where('type', 'school_fee')
            ->where('session_id', $currentSession?->id)
            ->first()?->status ?? 'unpaid';

        return Inertia::render('Student/Courses/Index', [
            'history' => $formattedHistory,
            'student' => $student,
            'schoolFeeStatus' => $schoolFeeStatus,
            'hasPendingInvoices' => $pendingInvoicesCount > 0,
            'isSecondSemester' => $isSecondSemActive,
        ]);
    }

    /**
     * Show course registration form.
     */
    public function create(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->with('academicDepartment')->first();

        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'You are not yet a matriculated student.');
        }

        $currentSession = Session::current();
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        if (!$currentSession->registration_enabled) {
            return back()->with('error', 'Course registration is currently closed for this session.');
        }

        $paymentInfo = $this->resolveSchoolFeePayment((string) Auth::id(), (string) $currentSession->id);

        if (!$paymentInfo['hasPaid']) {
            return redirect()->route('student.payments.index')
                ->with('error', 'You must pay your School Fees for the current session before registering courses.');
        }

        $currentSemester = Semester::current();
        $semesters = Semester::where('session_id', $currentSession->id)->orderBy('name')->get();
        $locks = $this->getSemesterRegistrationLocks($semesters, $paymentInfo['isPartial']);

        $department = $student->academicDepartment;
        if (!$department && !empty($student->department)) {
            $department = \App\Models\Department::where('name', $student->department)->first();
        }

        if (!$department) {
            return back()->with('error', 'No department assigned to your student profile.');
        }

        $programme = $student->program;
        $maxUnits = $programme ? $programme->max_credit_units : 24;

        $faculties = AcademicCacheService::getAllFaculties();
        $departments = AcademicCacheService::getAcademicDepartments();

        $availableCourses = $this->getAvailableCourses($student, $currentSession, $department, $request);

        $registeredCourses = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->with('course')
            ->get()
            ->pluck('course');

        $registeredCourseIds = $registeredCourses->pluck('id');

        $perCourseFeeConfig = FeeConfiguration::where('session_id', $currentSession->id)
            ->where('is_per_course', true)
            ->where(function ($q) use ($currentSemester) {
                if ($currentSemester) {
                    $q->where('semester_id', $currentSemester->id)->orWhereNull('semester_id');
                }
            })
            ->with('feeType')
            ->first();

        return Inertia::render('Student/Courses/Form', [
            'student' => $student,
            'session' => $currentSession,
            'semesters' => $semesters,
            'locks' => $locks,
            'isPartialPayment' => $paymentInfo['isPartial'],
            'faculties' => $faculties,
            'departments' => $departments,
            'courses' => $availableCourses,
            'registeredCourses' => $registeredCourses,
            'registeredCourseIds' => $registeredCourseIds,
            'maxUnits' => $maxUnits,
            'perCourseFeeConfig' => $perCourseFeeConfig ? [
                'amount' => (float) $perCourseFeeConfig->amount,
                'is_per_course' => true,
                'fee_type' => $perCourseFeeConfig->feeType,
            ] : null,
            'filters' => [
                'level' => $request->input('level', $student->current_level),
                'faculty_id' => $request->input('faculty_id'),
                'department_id' => $request->input('department_id'),
            ],
        ]);
    }

    /**
     * Store student course registration choices.
     */
    public function store(Request $request)
    {
        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
        ]);

        $student = Student::where('user_id', Auth::id())->firstOrFail();
        $currentSession = Session::current();
        if (!$currentSession) {
            abort(404, 'No active session.');
        }

        $paymentInfo = $this->resolveSchoolFeePayment((string) Auth::id(), (string) $currentSession->id);

        if (!$paymentInfo['hasPaid']) {
            return redirect()->route('student.payments.index')
                ->with('error', 'You must pay your School Fees for the current session before registering courses.');
        }

        $currentSemester = Semester::current();
        $semesters = Semester::where('session_id', $currentSession->id)->get();
        $firstSemester = $semesters->filter(fn ($s) => stripos($s->name, 'First') !== false || $s->name == '1')->first();
        $secondSemester = $semesters->filter(fn ($s) => stripos($s->name, 'Second') !== false || $s->name == '2')->first();

        $now = now();
        $isFirstSemLocked = false;
        if ($firstSemester) {
            if (($firstSemester->registration_starts_at && $now->lt($firstSemester->registration_starts_at)) ||
                ($firstSemester->registration_ends_at && $now->gt($firstSemester->registration_ends_at))) {
                $isFirstSemLocked = true;
            }
        }
        if ($secondSemester && $secondSemester->is_current) {
            $isFirstSemLocked = true;
        }

        $isSecondSemLocked = false;
        if ($secondSemester) {
            if (($secondSemester->registration_starts_at && $now->lt($secondSemester->registration_starts_at)) ||
                ($secondSemester->registration_ends_at && $now->gt($secondSemester->registration_ends_at))) {
                $isSecondSemLocked = true;
            }
        }
        if ($paymentInfo['isPartial']) {
            $isSecondSemLocked = true;
        }

        $programme = $student->program;
        $maxUnits = $programme ? $programme->max_credit_units : 24;

        $selectedCourses = Course::whereIn('id', $request->courses)->get();
        $firstSemCourses = $selectedCourses->where('semester', '1');
        $secondSemCourses = $selectedCourses->where('semester', '2');

        if ($paymentInfo['isPartial'] && $secondSemCourses->isNotEmpty()) {
            return back()->with('error', 'Because you made a partial school fee payment, you are only allowed to register for First Semester courses. Full payment of school fees is required to register for Second Semester courses.');
        }

        if ($firstSemCourses->sum('units') > $maxUnits) {
            return back()->with('error', "Maximum of {$maxUnits} units allowed for First Semester.");
        }
        if ($secondSemCourses->sum('units') > $maxUnits) {
            return back()->with('error', "Maximum of {$maxUnits} units allowed for Second Semester.");
        }

        // Enforce Locks on Semester-specific changes
        $existingRegistrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $currentSession->id)
            ->with('course')
            ->get();

        $existingFirstSemIds = $existingRegistrations->filter(fn ($r) => $r->course?->semester === '1')->pluck('course_id')->toArray();
        $newFirstSemIds = $firstSemCourses->pluck('id')->toArray();

        if ($isFirstSemLocked && (array_diff($existingFirstSemIds, $newFirstSemIds) || array_diff($newFirstSemIds, $existingFirstSemIds))) {
            return back()->with('error', 'First Semester registration is locked and cannot be modified.');
        }

        $existingSecondSemIds = $existingRegistrations->filter(fn ($r) => $r->course?->semester === '2')->pluck('course_id')->toArray();
        $newSecondSemIds = $secondSemCourses->pluck('id')->toArray();

        if ($isSecondSemLocked && (array_diff($existingSecondSemIds, $newSecondSemIds) || array_diff($newSecondSemIds, $existingSecondSemIds))) {
            return back()->with('error', 'Second Semester registration is locked and cannot be modified.');
        }

        $this->syncCourseRegistrations($student, $currentSession, $firstSemester, $secondSemester, $firstSemCourses, $secondSemCourses);

        if ($currentSemester) {
            $perCourseFeeConfig = FeeConfiguration::where('session_id', $currentSession->id)
                ->where('is_per_course', true)
                ->where(function ($q) use ($currentSemester) {
                    $q->where('semester_id', $currentSemester->id)->orWhereNull('semester_id');
                })
                ->first();

            if ($perCourseFeeConfig) {
                $feeService = app(FeeService::class);
                $invoice = $feeService->generatePerCourseInvoice($student, $currentSession, $currentSemester, $selectedCourses);

                if ($invoice && $invoice->status === 'pending') {
                    return to_route('student.payments.index')
                        ->with('info', "Course registration submitted! A per-course fee invoice of ₦" . number_format($invoice->amount, 2) . " (" . $selectedCourses->count() . " courses) has been generated. Please complete payment.");
                }
            }
        }

        return to_route('student.courses.index')->with('success', 'Course registration updated successfully.');
    }

    /**
     * Download course form PDF.
     */
    public function downloadForm(Request $request)
    {
        $student = Student::where('user_id', Auth::id())
            ->with(['user', 'department.faculty', 'program'])
            ->firstOrFail();

        $sessionId = $request->query('session_id');
        $session = $sessionId ? Session::findOrFail($sessionId) : Session::where('is_current', true)->firstOrFail();

        $programme = $student->program;
        $overrides = $programme
            ? DB::table('course_programme')->where('programme_id', $programme->id)->pluck('is_compulsory', 'course_id')
            : collect();

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->with('course', 'semester')
            ->get();

        foreach ($registrations as $reg) {
            if ($reg->course) {
                $reg->course->is_compulsory = $overrides->has($reg->course->id) ? (bool) $overrides->get($reg->course->id) : false;
            }
        }

        $groupedRegistrations = $registrations->groupBy(fn ($reg) => $reg->semester?->name ?? 'Semester');

        if ($registrations->isEmpty()) {
            return response("No course registration records found for this session ({$session->name}). Please ensure you have registered courses.", 404);
        }

        $pdf = Pdf::loadView('documents.course_form', [
            'student' => $student,
            'registrations' => $groupedRegistrations,
            'session' => $session,
            'semester' => null,
            'total_units' => $registrations->sum('course.units'),
        ]);

        return $pdf->download('Course_Form.pdf');
    }

    /**
     * Download exam docket card PDF.
     */
    public function downloadExamCard(Request $request)
    {
        $examCardEnabled = filter_var(\App\Models\SystemSetting::get('enable_exam_card_download', true), FILTER_VALIDATE_BOOLEAN);
        if (!$examCardEnabled) {
            return back()->with('error', 'Exam card downloading is currently disabled by the administration.');
        }

        $student = Student::where('user_id', Auth::id())
            ->with(['user', 'department.faculty', 'program'])
            ->firstOrFail();

        $sessionId = $request->query('session_id');
        $semesterId = $request->query('semester_id');

        if ($semesterId) {
            $semester = Semester::findOrFail($semesterId);
            $session = $semester->session;
        } else {
            $session = $sessionId ? Session::findOrFail($sessionId) : Session::where('is_current', true)->firstOrFail();
            $semester = Semester::where('session_id', $session->id)->where('is_current', true)->first()
                ?? Semester::where('session_id', $session->id)->firstOrFail();
        }

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->where('semester_id', $semester->id)
            ->with('course')
            ->get();

        if ($registrations->isEmpty()) {
            return response("No registered courses found for {$semester->name} semester, {$session->name} session.", 404);
        }

        $currentSemester = Semester::current();
        $isSecondSemActive = $currentSemester && (stripos($currentSemester->name, 'Second') !== false || $currentSemester->name == '2');
        $isSecondSemRequested = str_contains(strtolower($semester->name), 'second') || $semester->name == '2';

        if ($isSecondSemActive || $isSecondSemRequested) {
            $hasSchoolFeePaid = Invoice::where('user_id', Auth::id())
                ->where('type', 'school_fee')
                ->where('session_id', $session->id)
                ->where('status', 'paid')
                ->exists();

            $pendingInvoicesCount = Invoice::where('user_id', Auth::id())
                ->where('status', '!=', 'paid')
                ->count();

            if (!$hasSchoolFeePaid || $pendingInvoicesCount > 0) {
                return back()->with('error', 'Second Semester Exam Card is only available after full payment of all pending fees, including school fees and hostel fees. Please clear your outstanding balance.');
            }
        }

        $globalPublished = filter_var(\App\Models\SystemSetting::get('publish_exam_timetable', false), FILTER_VALIDATE_BOOLEAN);
        $hasPublishedExercise = \App\Models\Exam::where('is_published', true)->exists();
        $isExamPublished = $globalPublished || $hasPublishedExercise;
        $registeredCourseIds = $registrations->pluck('course_id');
        $examSchedules = collect([]);

        if ($isExamPublished) {
            $examSchedules = \App\Models\ExamSchedule::whereIn('course_id', $registeredCourseIds)
                ->where('session_id', $session->id)
                ->get()
                ->keyBy('course_id');
        }

        $verificationToken = strtoupper(md5($student->id . $session->id . ($semester->id ?? '') . 'EXAM_ATTENDANCE_SECRET'));
        $qrCodeData = $verificationToken;
        $directApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrCodeData);

        $qrCodeUrl = $directApiUrl;
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get($directApiUrl);
            if ($response->successful()) {
                $qrCodeUrl = 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('QR Code fetch failed: ' . $e->getMessage());
        }

        $pdf = Pdf::loadView('documents.exam_card', [
            'student' => $student,
            'registrations' => $registrations,
            'examSchedules' => $examSchedules,
            'isExamPublished' => $isExamPublished,
            'session' => $session,
            'semester' => $semester,
            'verificationToken' => $verificationToken,
            'qrCodeUrl' => $qrCodeUrl,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf->download("Exam_Card_{$semester->name}.pdf");
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function resolveSchoolFeePayment(string $userId, string $sessionId): array
    {
        $schoolFeeInvoice = Invoice::where('user_id', $userId)
            ->where('type', 'school_fee')
            ->where('session_id', $sessionId)
            ->first();

        $hasPaid = $schoolFeeInvoice && (
            $schoolFeeInvoice->status === 'paid' ||
            $schoolFeeInvoice->status === 'partial' ||
            (float) $schoolFeeInvoice->paid_amount > 0
        );

        $isPartial = $schoolFeeInvoice && (
            $schoolFeeInvoice->status === 'partial' ||
            ((float) $schoolFeeInvoice->paid_amount > 0 && (float) $schoolFeeInvoice->paid_amount < (float) $schoolFeeInvoice->amount)
        );

        return [
            'invoice' => $schoolFeeInvoice,
            'hasPaid' => $hasPaid,
            'isPartial' => $isPartial,
            'status' => $schoolFeeInvoice?->status ?? 'unpaid',
        ];
    }

    private function getSemesterRegistrationLocks($semesters, bool $isPartialPayment): array
    {
        $firstSemester = $semesters->filter(fn ($s) => stripos($s->name, 'First') !== false || $s->name == '1')->first();
        $secondSemester = $semesters->filter(fn ($s) => stripos($s->name, 'Second') !== false || $s->name == '2')->first();

        $now = now();
        $locks = ['1' => false, '2' => false];

        if ($firstSemester) {
            if (($firstSemester->registration_starts_at && $now->lt($firstSemester->registration_starts_at)) ||
                ($firstSemester->registration_ends_at && $now->gt($firstSemester->registration_ends_at))) {
                $locks['1'] = true;
            }
        }
        if ($secondSemester && $secondSemester->is_current) {
            $locks['1'] = true;
        }

        if ($secondSemester) {
            if (($secondSemester->registration_starts_at && $now->lt($secondSemester->registration_starts_at)) ||
                ($secondSemester->registration_ends_at && $now->gt($secondSemester->registration_ends_at))) {
                $locks['2'] = true;
            }
        }

        if ($isPartialPayment) {
            $locks['2'] = true;
        }

        return $locks;
    }

    private function getAvailableCourses(Student $student, Session $session, $department, Request $request)
    {
        $query = Course::query();

        $level = $request->input('level', $student->current_level);
        if ($level) {
            $query->where('level', $level);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        } elseif ($request->filled('faculty_id')) {
            $deptIds = \App\Models\Department::where('faculty_id', $request->faculty_id)->pluck('id');
            $query->whereIn('department_id', $deptIds);
        } else {
            if ($department) {
                $query->where('department_id', $department->id);
            }
        }

        $courses = $query->with([
            'department',
            'allocations' => function ($q) use ($session) {
                $q->where('session_id', $session->id)->with('staff.user');
            },
        ])->orderBy('semester')->orderBy('code')->get();

        if ($student->program) {
            $overrides = DB::table('course_programme')
                ->where('programme_id', $student->program->id)
                ->pluck('is_compulsory', 'course_id');

            $courses->transform(function ($course) use ($overrides) {
                if ($overrides->has($course->id)) {
                    $course->is_compulsory = (bool) $overrides->get($course->id);
                }
                return $course;
            });
        }

        return $courses;
    }

    private function syncCourseRegistrations(Student $student, Session $session, ?Semester $firstSemester, ?Semester $secondSemester, $firstSemCourses, $secondSemCourses): void
    {
        DB::transaction(function () use ($student, $session, $firstSemester, $secondSemester, $firstSemCourses, $secondSemCourses) {
            $studentSession = StudentSession::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'session_id' => $session->id,
                ],
                [
                    'level' => $student->current_level,
                    'status' => 'active',
                ]
            );

            CourseRegistration::where('student_id', $student->id)
                ->where('session_id', $session->id)
                ->delete();

            if ($firstSemester) {
                foreach ($firstSemCourses as $course) {
                    CourseRegistration::create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'session_id' => $session->id,
                        'semester_id' => $firstSemester->id,
                        'student_session_id' => $studentSession->id,
                    ]);
                }
            }

            if ($secondSemester) {
                foreach ($secondSemCourses as $course) {
                    CourseRegistration::create([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'session_id' => $session->id,
                        'semester_id' => $secondSemester->id,
                        'student_session_id' => $studentSession->id,
                    ]);
                }
            }
        });

        AcademicCacheService::clearTimetableCache();
    }
}
