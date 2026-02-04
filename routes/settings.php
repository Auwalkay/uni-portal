<?php

use App\Http\Controllers\Admin\CourseRegistrationController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Staff\CourseController;
use App\Http\Controllers\Student\PaymentController;
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
    Route::prefix('applicant')->name('applicant.')->middleware(['role:applicant'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Applicant\ApplicationController::class, 'index'])->name('dashboard');
        Route::get('/apply/start', [\App\Http\Controllers\Applicant\ApplicationController::class, 'create'])->name('apply.start');
        Route::get('/apply/form', [\App\Http\Controllers\Applicant\ApplicationController::class, 'form'])->name('apply.form');
        Route::post('/apply', [\App\Http\Controllers\Applicant\ApplicationController::class, 'store'])->name('apply.store');
        Route::get('/application/preview', [\App\Http\Controllers\Applicant\ApplicationController::class, 'show'])->name('apply.show');

        Route::get('/payment', [\App\Http\Controllers\Applicant\PaymentController::class, 'index'])->name('payment.index');
        Route::post('/payment/pay', [\App\Http\Controllers\Applicant\PaymentController::class, 'pay'])->name('payment.pay');
        Route::get('/payment/callback', [\App\Http\Controllers\Applicant\PaymentController::class, 'callback'])->name('payment.callback');

        Route::post('/accept-offer', [\App\Http\Controllers\Applicant\ApplicationController::class, 'acceptOffer'])->name('accept.offer');
    });

    // External API Simulations
    Route::prefix('api/external')->group(function () {
        Route::post('/jamb/fetch', [\App\Http\Controllers\External\JambController::class, 'fetchDetails']);
    });

    // STUDENT Routes
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Student\ProfileController::class, 'dashboard'])->name('dashboard');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/create-school-fee', [PaymentController::class, 'createSchoolFeeInvoice'])->name('payments.create_school_fee');
        Route::post('/payments/{invoice}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
        Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');

        Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');

        Route::get('/courses', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'index'])->name('courses.index');
        Route::get('/courses/register', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'create'])->name('courses.create');
        Route::post('/courses', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'store'])->name('courses.store');
        Route::get('/courses/form', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'downloadForm'])->name('courses.form');
        Route::get('/courses/exam-card', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'downloadExamCard'])->name('courses.exam_card');

        Route::get('/timetable', [\App\Http\Controllers\Student\TimetableController::class, 'index'])->name('timetable.index');

        Route::get('/results', [\App\Http\Controllers\Student\ResultController::class, 'index'])->name('results.index');

        Route::get('/id-card', [\App\Http\Controllers\Student\IdCardController::class, 'show'])->name('id_card.show');
        Route::get('/admission-letter', [\App\Http\Controllers\Student\ProfileController::class, 'downloadAdmissionLetter'])->name('admission_letter.download');
    });

    // ADMIN & STAFF ROUTES
    Route::prefix('admin')->name('admin.')->middleware(['role:admin|registrar|dean|hod|course_coordinator|lecturer|admissions_manager|admissions_officer|admissions_clerk|bursar|finance_officer|finance_clerk|receptionist'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Front Desk Module
        Route::middleware(['role:admin|receptionist'])->prefix('front-desk')->name('front-desk.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\FrontDesk\DashboardController::class, 'index'])->name('dashboard');

            Route::resource('visitors', \App\Http\Controllers\Admin\FrontDesk\VisitorController::class);
            Route::resource('complaints', \App\Http\Controllers\Admin\FrontDesk\ComplaintController::class);
            Route::resource('enquiries', \App\Http\Controllers\Admin\FrontDesk\EnquiryController::class);
        });

        // Admissions Management
        Route::middleware(['role:admin|admissions_manager|admissions_officer|admissions_clerk'])->group(function () {
            Route::get('/admissions', [\App\Http\Controllers\Admin\AdmissionController::class, 'index'])->name('admissions.index');
            Route::get('/admissions/{applicant}', [\App\Http\Controllers\Admin\AdmissionController::class, 'show'])->name('admissions.show');
            Route::get('/admissions/{applicant}/letter', [\App\Http\Controllers\Admin\AdmissionController::class, 'downloadLetter'])->name('admissions.letter');
            Route::get('/documents/{document}', [\App\Http\Controllers\Admin\DocumentController::class, 'show'])->name('documents.show');

            // Restricted Admissions Actions
            Route::middleware(['role:admin|admissions_manager|admissions_officer'])->group(function () {
                Route::put('/admissions/{applicant}', [\App\Http\Controllers\Admin\AdmissionController::class, 'update'])->name('admissions.update');
            });
        });

        // Results Management
        Route::middleware(['role:admin|registrar|dean|hod|course_coordinator|lecturer'])->group(function () {
            Route::get('/results', [\App\Http\Controllers\Admin\ResultController::class, 'index'])->name('results.index');
            Route::get('/results/{course}/entry', [\App\Http\Controllers\Admin\ResultController::class, 'edit'])->name('results.edit');
            Route::post('/results/{course}', [\App\Http\Controllers\Admin\ResultController::class, 'update'])->name('results.update');
            Route::post('/results/{course}/upload', [\App\Http\Controllers\Admin\ResultController::class, 'upload'])->name('results.upload');
        });

        // Student Management (Creation & Migration)
        Route::middleware(['role:admin|registrar'])->group(function () {
            Route::get('/students/create', [\App\Http\Controllers\Admin\StudentController::class, 'create'])->name('students.create');
            Route::post('/students', [\App\Http\Controllers\Admin\StudentController::class, 'store'])->name('students.store');
            Route::post('/students/import', [\App\Http\Controllers\Admin\StudentController::class, 'import'])->name('students.import');
            Route::get('/students/template', [\App\Http\Controllers\Admin\StudentController::class, 'downloadTemplate'])->name('students.template');
        });

        // Search & View Students (All Staff)
        Route::get('/students', [\App\Http\Controllers\Admin\StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'show'])->name('students.show');

        // Course Registrations & Academic Management
        Route::middleware(['role:admin|registrar|dean|hod'])->group(function () {
            Route::get('/courses/{course}/registrations', [CourseRegistrationController::class, 'index'])->name('courses.registrations.index');
            Route::get('/courses/{course}/registrations/export', [CourseRegistrationController::class, 'export'])->name('courses.registrations.export');

            // Moved user management to system settings group
        });

        // Staff Course Management (Teaching)
        Route::middleware(['role:admin|lecturer|course_coordinator'])->prefix('teaching')->name('teaching.')->group(function () {
            Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
        });

        // Finance & Payments
        Route::middleware(['role:admin|bursar|finance_officer|finance_clerk'])->group(function () {
            Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('payments.show');
            Route::get('/finance', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index');

            // Restricted Finance Actions
            Route::middleware(['role:admin|bursar|finance_officer'])->group(function () {
                Route::post('/finance/fee-types', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeType'])->name('finance.fee_types.store');
                Route::put('/finance/fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeType'])->name('finance.fee_types.update');
                Route::post('/finance/configurations', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeConfiguration'])->name('finance.configurations.store');
                Route::put('/finance/configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeConfiguration'])->name('finance.configurations.update');
                Route::get('/finance/sessions/{session}/fees', [\App\Http\Controllers\Admin\FinanceController::class, 'manageSessionFees'])->name('finance.session.fees');
            });

            Route::middleware(['role:admin|bursar'])->group(function () {
                Route::delete('/finance/fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeType'])->name('finance.fee_types.destroy');
                Route::delete('/finance/configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeConfiguration'])->name('finance.configurations.destroy');
            });
        });

        // General Academics & Sessions
        Route::middleware(['role:admin|registrar|dean|hod'])->group(function () {
            Route::get('/academics', [\App\Http\Controllers\Admin\AcademicController::class, 'index'])->name('academics.index');
            Route::post('/academics/store', [\App\Http\Controllers\Admin\AcademicController::class, 'store'])->name('academics.store');
            Route::post('/academics/update', [\App\Http\Controllers\Admin\AcademicController::class, 'update'])->name('academics.update');
            Route::post('/academics/toggle', [\App\Http\Controllers\Admin\AcademicController::class, 'toggle'])->name('academics.toggle');

            Route::get('/sessions', [\App\Http\Controllers\Admin\SessionController::class, 'index'])->name('sessions.index');
            Route::get('/sessions/{session}', [\App\Http\Controllers\Admin\SessionController::class, 'show'])->name('sessions.show');

            // Timetable Management
            Route::post('timetables/import', [\App\Http\Controllers\Admin\TimetableController::class, 'import'])->name('timetables.import');
            Route::get('timetables/template', [\App\Http\Controllers\Admin\TimetableController::class, 'template'])->name('timetables.template');
            Route::resource('timetables', \App\Http\Controllers\Admin\TimetableController::class)->only(['index', 'store', 'destroy']);

            // Restricted Session Management
            Route::middleware(['role:admin|registrar'])->group(function () {
                Route::post('/sessions', [\App\Http\Controllers\Admin\SessionController::class, 'store'])->name('sessions.store');
                Route::put('/sessions/{session}', [\App\Http\Controllers\Admin\SessionController::class, 'update'])->name('sessions.update');
                Route::put('/sessions/{session}/settings', [\App\Http\Controllers\Admin\SessionController::class, 'updateSettings'])->name('sessions.settings');
                Route::post('/sessions/{session}/fees', [\App\Http\Controllers\Admin\SessionController::class, 'storeFee'])->name('sessions.fees.store');
                Route::delete('/sessions/{session}/fees/{feeConfiguration}', [\App\Http\Controllers\Admin\SessionController::class, 'destroyFee'])->name('sessions.fees.destroy');
                Route::post('/sessions/{session}/activation', [\App\Http\Controllers\Admin\SessionController::class, 'activate'])->name('sessions.activate');
                Route::post('/sessions/{session}/toggle-registration', [\App\Http\Controllers\Admin\SessionController::class, 'toggleRegistration'])->name('sessions.toggle_registration');
                Route::post('/sessions/{session}/semesters/{semester}/activate', [\App\Http\Controllers\Admin\SessionController::class, 'activateSemester'])->name('sessions.semesters.activate');
                Route::put('/sessions/{session}/semesters/{semester}', [\App\Http\Controllers\Admin\SessionController::class, 'updateSemester'])->name('sessions.semesters.update');
            });
        });

        // System Settings & RBAC Management
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('settings.index');

            Route::get('/settings/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('settings.roles.index');
            Route::get('/settings/roles/{role}/edit', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('settings.roles.edit');
            Route::put('/settings/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('settings.roles.update');
            Route::get('/settings/logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('settings.logs.index');

            Route::patch('/users/{user}/roles', [\App\Http\Controllers\Admin\UserController::class, 'updateRoles'])->name('users.roles.update');
            Route::patch('/users/{user}/status', [\App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])->name('users.status.toggle');

            Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
            Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
            Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
        });
    });

});
