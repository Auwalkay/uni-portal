<?php

use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Admin\CourseRegistrationController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\FrontDesk\ComplaintController;
use App\Http\Controllers\Admin\FrontDesk\DashboardController;
use App\Http\Controllers\Admin\FrontDesk\EnquiryController;
use App\Http\Controllers\Admin\FrontDesk\VisitorController;
use App\Http\Controllers\Admin\HostelBookingController;
use App\Http\Controllers\Admin\HostelController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\ScholarshipController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Applicant\ApplicationController;
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
    Route::prefix('applicant')->name('applicant.')->middleware(['permission:access_applicant_portal'])->group(function () {
        Route::get('/dashboard', [ApplicationController::class, 'index'])->name('dashboard');
        Route::get('/apply/start', [ApplicationController::class, 'create'])->name('apply.start');
        Route::get('/apply/form', [ApplicationController::class, 'form'])->name('apply.form');
        Route::post('/apply', [ApplicationController::class, 'store'])->name('apply.store');
        Route::get('/application/preview', [ApplicationController::class, 'show'])->name('apply.show');

        Route::get('/payment', [\App\Http\Controllers\Applicant\PaymentController::class, 'index'])->name('payment.index');
        Route::post('/payment/pay', [\App\Http\Controllers\Applicant\PaymentController::class, 'pay'])->name('payment.pay');
        Route::get('/payment/callback', [\App\Http\Controllers\Applicant\PaymentController::class, 'callback'])->name('payment.callback');

        Route::post('/accept-offer', [ApplicationController::class, 'acceptOffer'])->name('accept.offer');
    });

    // External API Simulations
    Route::prefix('api/external')->group(function () {
        Route::post('/jamb/fetch', [\App\Http\Controllers\External\JambController::class, 'fetchDetails']);
    });

    // STUDENT Routes
    Route::prefix('student')->name('student.')->middleware('permission:access_student_portal')->group(function () {
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

        Route::get('/accommodation', [\App\Http\Controllers\Student\AccommodationController::class, 'index'])->name('accommodation.index');
        Route::post('/accommodation', [\App\Http\Controllers\Student\AccommodationController::class, 'store'])->name('accommodation.store');
        Route::get('/accommodation/download-slip', [\App\Http\Controllers\Student\AccommodationController::class, 'downloadAccommodationSlip'])->name('accommodation.download-slip');
        Route::get('/accommodation/download-payment', [\App\Http\Controllers\Student\AccommodationController::class, 'downloadPaymentSlip'])->name('accommodation.download-payment');

        Route::get('/id-card', [\App\Http\Controllers\Student\IdCardController::class, 'show'])->name('id_card.show');
        Route::get('/admission-letter', [\App\Http\Controllers\Student\ProfileController::class, 'downloadAdmissionLetter'])->name('admission_letter.download');
    });

    // ADMIN & STAFF ROUTES
    Route::prefix('admin')->name('admin.')->middleware(['permission:access_admin_dashboard'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Front Desk Module
        Route::middleware(['permission:manage_visitors'])->prefix('front-desk')->name('front-desk.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            Route::resource('visitors', VisitorController::class);
            Route::resource('complaints', ComplaintController::class);
            Route::resource('enquiries', EnquiryController::class);
        });

        // Admissions Management
        Route::middleware(['permission:view_applications'])->group(function () {
            Route::get('/admissions', [AdmissionController::class, 'index'])->name('admissions.index');
            Route::get('/admissions/{applicant}', [AdmissionController::class, 'show'])->name('admissions.show');
            Route::get('/admissions/{applicant}/letter', [AdmissionController::class, 'downloadLetter'])->name('admissions.letter');
            Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');

            // Restricted Admissions Actions
            Route::middleware(['permission:review_applications'])->group(function () {
                Route::put('/admissions/{applicant}', [AdmissionController::class, 'update'])->name('admissions.update');
            });
        });

        // Results Management
        Route::middleware(['permission:view_results'])->group(function () {
            Route::get('/results', [ResultController::class, 'index'])->name('results.index');
            Route::get('/results/{course}/entry', [ResultController::class, 'edit'])->name('results.edit');
            Route::post('/results/{course}', [ResultController::class, 'update'])->name('results.update');
            Route::post('/results/{course}/upload', [ResultController::class, 'upload'])->name('results.upload');
            Route::post('/results/{course}/publish', [ResultController::class, 'publish'])->name('results.publish');
        });

        // Student Management (Creation & Migration)
        Route::middleware(['permission:manage_staff'])->group(function () {
            Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
            Route::post('/students', [StudentController::class, 'store'])->name('students.store');
            Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
            Route::get('/students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
            Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
            Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        });

        // Search & View Students (All Staff)
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

        // Course Registrations & Academic Management
        Route::middleware(['permission:manage_academic_sessions'])->group(function () {
            Route::get('/courses/{course}/registrations', [CourseRegistrationController::class, 'index'])->name('courses.registrations.index');
            Route::get('/courses/{course}/registrations/export', [CourseRegistrationController::class, 'export'])->name('courses.registrations.export');

            // Moved user management to system settings group
        });

        // Staff Course Management (Teaching)
        Route::middleware(['permission:view_results'])->prefix('teaching')->name('teaching.')->group(function () {
            Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
            Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
        });

        // Finance & Payments
        Route::middleware(['permission:view_payments'])->group(function () {
            Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('payments.show');
            Route::get('/finance', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index');

            // Restricted Finance Actions
            Route::middleware(['permission:verify_payments'])->group(function () {
                Route::post('/finance/fee-types', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeType'])->name('finance.fee_types.store');
                Route::put('/finance/fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeType'])->name('finance.fee_types.update');
                Route::post('/finance/configurations', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeConfiguration'])->name('finance.configurations.store');
                Route::put('/finance/configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeConfiguration'])->name('finance.configurations.update');
                Route::get('/finance/sessions/{session}/fees', [\App\Http\Controllers\Admin\FinanceController::class, 'manageSessionFees'])->name('finance.session.fees');

                Route::resource('/scholarships', ScholarshipController::class)
                    ->except(['create', 'edit', 'show'])
                    ->names('scholarships');
            });

            Route::middleware(['permission:manage_payments'])->group(function () {
                Route::delete('/finance/fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeType'])->name('finance.fee_types.destroy');
                Route::delete('/finance/configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeConfiguration'])->name('finance.configurations.destroy');
            });
        });

        // General Academics & Sessions
        Route::middleware(['permission:manage_academic_sessions'])->group(function () {
            Route::get('/academics', [\App\Http\Controllers\Admin\AcademicController::class, 'index'])->name('academics.index');
            Route::post('/academics/store', [\App\Http\Controllers\Admin\AcademicController::class, 'store'])->name('academics.store');
            Route::post('/academics/update', [\App\Http\Controllers\Admin\AcademicController::class, 'update'])->name('academics.update');
            Route::post('/academics/toggle', [\App\Http\Controllers\Admin\AcademicController::class, 'toggle'])->name('academics.toggle');

            Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
            Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');

            // Timetable Management
            Route::post('timetables/import', [\App\Http\Controllers\Admin\TimetableController::class, 'import'])->name('timetables.import');
            Route::get('timetables/template', [\App\Http\Controllers\Admin\TimetableController::class, 'template'])->name('timetables.template');
            Route::resource('timetables', \App\Http\Controllers\Admin\TimetableController::class)->only(['index', 'store', 'destroy']);

            // Hostel Management
            Route::get('hostels/bookings', [HostelBookingController::class, 'index'])->name('hostels.bookings.index');
            Route::resource('hostels', HostelController::class);

            Route::post('hostels/{hostel}/blocks', [\App\Http\Controllers\Admin\HostelBlockController::class, 'store'])->name('hostels.blocks.store');
            Route::delete('hostels/{hostel}/blocks/{block}', [\App\Http\Controllers\Admin\HostelBlockController::class, 'destroy'])->name('hostels.blocks.destroy');

            Route::post('hostels/{hostel}/blocks/{block}/floors', [\App\Http\Controllers\Admin\HostelFloorController::class, 'store'])->name('hostels.floors.store');
            Route::delete('hostels/{hostel}/blocks/{block}/floors/{floor}', [\App\Http\Controllers\Admin\HostelFloorController::class, 'destroy'])->name('hostels.floors.destroy');

            Route::post('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms', [\App\Http\Controllers\Admin\HostelRoomController::class, 'store'])->name('hostels.rooms.store');
            Route::put('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms/{room}', [\App\Http\Controllers\Admin\HostelRoomController::class, 'update'])->name('hostels.rooms.update');
            Route::delete('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms/{room}', [\App\Http\Controllers\Admin\HostelRoomController::class, 'destroy'])->name('hostels.rooms.destroy');

            // Fees
            Route::post('hostels/fees', [\App\Http\Controllers\Admin\HostelFeeController::class, 'store'])->name('hostels.fees.store');
            Route::delete('hostels/fees/{fee}', [\App\Http\Controllers\Admin\HostelFeeController::class, 'destroy'])->name('hostels.fees.destroy');

            // Restricted Session Management
            Route::middleware(['permission:manage_academic_sessions'])->group(function () {
                Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
                Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
                Route::put('/sessions/{session}/settings', [SessionController::class, 'updateSettings'])->name('sessions.settings');
                Route::post('/sessions/{session}/fees', [SessionController::class, 'storeFee'])->name('sessions.fees.store');
                Route::delete('/sessions/{session}/fees/{feeConfiguration}', [SessionController::class, 'destroyFee'])->name('sessions.fees.destroy');
                Route::post('/sessions/{session}/activation', [SessionController::class, 'activate'])->name('sessions.activate');
                Route::post('/sessions/{session}/toggle-registration', [SessionController::class, 'toggleRegistration'])->name('sessions.toggle_registration');
                Route::post('/sessions/{session}/semesters/{semester}/activate', [SessionController::class, 'activateSemester'])->name('sessions.semesters.activate');
                Route::put('/sessions/{session}/semesters/{semester}', [SessionController::class, 'updateSemester'])->name('sessions.semesters.update');
            });
        });

        // System Settings & RBAC Management
        Route::middleware(['permission:manage_system_settings'])->group(function () {
            Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings/update', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateSetting'])->name('settings.update');

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
