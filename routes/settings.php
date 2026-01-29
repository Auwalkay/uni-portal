<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // APPLICANT ROUTES
    Route::prefix('applicant')->name('applicant.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Applicant\ApplicationController::class, 'index'])->name('dashboard');
        Route::get('/apply/start', [\App\Http\Controllers\Applicant\ApplicationController::class, 'create'])->name('apply.start');
        Route::get('/apply/form', [\App\Http\Controllers\Applicant\ApplicationController::class, 'form'])->name('apply.form');
        Route::post('/apply', [\App\Http\Controllers\Applicant\ApplicationController::class, 'store'])->name('apply.store');
        Route::post('/accept-offer', [\App\Http\Controllers\Applicant\ApplicationController::class, 'acceptOffer'])->name('accept.offer');
    });

    // External API Simulations
    Route::prefix('api/external')->group(function () {
        Route::post('/jamb/fetch', [\App\Http\Controllers\External\JambController::class, 'fetchDetails']);
    });

    // STUDENT Routes
    Route::prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', function () {
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
                    if ($start && $now->lt($start))
                        $isOpen = false;
                    if ($end && $now->gt($end))
                        $isOpen = false;

                    if ($isOpen) {
                        // Check if already registered
                        $hasRegistered = \App\Models\CourseRegistration::where('student_id', $student->id)
                            ->where('session_id', $currentSession->id)
                            ->where('semester_id', $currentSemester->id)
                            ->exists();

                        if (!$hasRegistered) {
                            $showRegistrationNotification = true;
                            if ($end) {
                                $registrationMessage = "Registration for {$currentSemester->name} closes on " . $end->format('M d, Y') . ". Register now to avoid penalties.";
                            } else {
                                $registrationMessage = "Registration for {$currentSemester->name} is now open.";
                            }
                        }
                    }
                }
            }

            // Check School Fee status
            $hasPaidSchoolFee = \App\Models\Invoice::where('user_id', auth()->id())
                ->where('type', 'school_fee')
                ->where('status', 'paid')
                ->exists();

            return Inertia::render('Student/Dashboard', [
                'student' => $student,
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
                ]
            ]);
        })->name('dashboard');

        Route::get('/payments', [\App\Http\Controllers\Student\PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/create-school-fee', [\App\Http\Controllers\Student\PaymentController::class, 'createSchoolFeeInvoice'])->name('payments.create_school_fee');
        Route::post('/payments/{invoice}/pay', [\App\Http\Controllers\Student\PaymentController::class, 'pay'])->name('payments.pay');
        Route::get('/payments/callback', [\App\Http\Controllers\Student\PaymentController::class, 'callback'])->name('payments.callback');

        Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');

        Route::get('/courses', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'index'])->name('courses.index');
        Route::get('/courses/register', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'create'])->name('courses.create');
        Route::post('/courses', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'store'])->name('courses.store');
        Route::get('/courses/form', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'downloadForm'])->name('courses.form');

        Route::get('/results', [\App\Http\Controllers\Student\ResultController::class, 'index'])->name('results.index');
    });

    // ADMIN ROUTES
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        Route::get('/admissions', [\App\Http\Controllers\Admin\AdmissionController::class, 'index'])->name('admissions.index');
        Route::get('/admissions/{applicant}', [\App\Http\Controllers\Admin\AdmissionController::class, 'show'])->name('admissions.show');
        Route::get('/admissions/{applicant}/letter', [\App\Http\Controllers\Admin\AdmissionController::class, 'downloadLetter'])->name('admissions.letter');
        Route::put('/admissions/{applicant}', [\App\Http\Controllers\Admin\AdmissionController::class, 'update'])->name('admissions.update');
        Route::get('/documents/{document}', [\App\Http\Controllers\Admin\DocumentController::class, 'show'])->name('documents.show');

        // Results
        Route::get('/results', [\App\Http\Controllers\Admin\ResultController::class, 'index'])->name('results.index');
        Route::get('/results/{course}/entry', [\App\Http\Controllers\Admin\ResultController::class, 'edit'])->name('results.edit');
        Route::post('/results/{course}', [\App\Http\Controllers\Admin\ResultController::class, 'update'])->name('results.update');

        // Students
        Route::get('/students', [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('students.index');

        // Staff & Users
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');

        // Payments
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');

        // Finance Management
        Route::get('/finance', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/fee-types', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeType'])->name('finance.fee_types.store');
        Route::put('/finance/fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeType'])->name('finance.fee_types.update');
        Route::delete('/finance/fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeType'])->name('finance.fee_types.destroy');

        Route::post('/finance/configurations', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeConfiguration'])->name('finance.configurations.store');
        Route::put('/finance/configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeConfiguration'])->name('finance.configurations.update');
        Route::delete('/finance/configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeConfiguration'])->name('finance.configurations.destroy');

        // Academics
        Route::get('/academics', [\App\Http\Controllers\Admin\AcademicController::class, 'index'])->name('academics.index');
        Route::post('/academics/store', [\App\Http\Controllers\Admin\AcademicController::class, 'store'])->name('academics.store');
        Route::post('/academics/update', [\App\Http\Controllers\Admin\AcademicController::class, 'update'])->name('academics.update');
        Route::post('/academics/toggle', [\App\Http\Controllers\Admin\AcademicController::class, 'toggle'])->name('academics.toggle');

        // Sessions
        Route::get('/sessions', [\App\Http\Controllers\Admin\SessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [\App\Http\Controllers\Admin\SessionController::class, 'store'])->name('sessions.store');
        Route::put('/sessions/{session}', [\App\Http\Controllers\Admin\SessionController::class, 'update'])->name('sessions.update');
        Route::get('/sessions/{session}', [\App\Http\Controllers\Admin\SessionController::class, 'show'])->name('sessions.show');
        Route::put('/sessions/{session}/settings', [\App\Http\Controllers\Admin\SessionController::class, 'updateSettings'])->name('sessions.settings');
        Route::post('/sessions/{session}/fees', [\App\Http\Controllers\Admin\SessionController::class, 'storeFee'])->name('sessions.fees.store');
        Route::delete('/sessions/{session}/fees/{feeConfiguration}', [\App\Http\Controllers\Admin\SessionController::class, 'destroyFee'])->name('sessions.fees.destroy');
        Route::post('/sessions/{session}/activation', [\App\Http\Controllers\Admin\SessionController::class, 'activate'])->name('sessions.activate');
        Route::post('/sessions/{session}/toggle-registration', [\App\Http\Controllers\Admin\SessionController::class, 'toggleRegistration'])->name('sessions.toggle_registration');
        Route::post('/sessions/{session}/semesters/{semester}/activate', [\App\Http\Controllers\Admin\SessionController::class, 'activateSemester'])->name('sessions.semesters.activate');
        Route::put('/sessions/{session}/semesters/{semester}', [\App\Http\Controllers\Admin\SessionController::class, 'updateSemester'])->name('sessions.semesters.update');
    });
});
