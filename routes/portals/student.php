<?php

use App\Http\Controllers\Student\AccommodationController;
use App\Http\Controllers\Student\ExamDocketController;
use App\Http\Controllers\Student\IdCardController;
use App\Http\Controllers\Student\LibraryController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\StudentAnnouncementController;
use App\Http\Controllers\Student\SupportTicketController;
use App\Http\Controllers\Student\TimetableController;
use Illuminate\Support\Facades\Route;

// STUDENT Routes
Route::prefix('student')->name('student.')->middleware('permission:access_student_portal')->group(function () {
    // Profile routes (exempt from completion enforcement so they can complete it)
    Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/manual', [\App\Http\Controllers\Student\ProfileController::class, 'manual'])->name('manual');

    // Enforced routes
    Route::middleware(['student_profile_completed'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Student\ProfileController::class, 'dashboard'])->name('dashboard');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/invoices/{invoice}', [PaymentController::class, 'showInvoice'])->name('payments.invoice.show');
        Route::post('/payments/create-school-fee', [PaymentController::class, 'createSchoolFeeInvoice'])->name('payments.create_school_fee');
        Route::get('/payments/optional-fees', [PaymentController::class, 'getOptionalFees'])->name('payments.optional_fees');
        Route::post('/payments/initiate-optional/{config}', [PaymentController::class, 'initiateOptionalFee'])->name('payments.initiate_optional');
        Route::post('/payments/{invoice}/pay', [PaymentController::class, 'pay'])->name('payments.pay');
        Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');
        Route::get('/payments/{payment}/download', [PaymentController::class, 'downloadReceipt'])->name('payments.download');
        Route::post('/payments/{payment}/requery', [PaymentController::class, 'requery'])->name('payments.requery');
        Route::post('/payments/requery-reference', [PaymentController::class, 'requeryReference'])->name('payments.requery_reference');

        Route::get('/courses', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'index'])->name('courses.index');
        Route::get('/courses/register', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'create'])->name('courses.create');
        Route::post('/courses', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'store'])->name('courses.store');
        Route::get('/courses/form', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'downloadForm'])->name('courses.form');
        Route::get('/courses/exam-card', [\App\Http\Controllers\Student\CourseRegistrationController::class, 'downloadExamCard'])->name('courses.exam_card');

        Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');
        Route::get('/attendance', [\App\Http\Controllers\Student\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/exam-docket', [ExamDocketController::class, 'index'])->name('exam_docket.index');
        Route::get('/exam-docket-alt', [ExamDocketController::class, 'index'])->name('exam-docket.index');

        Route::get('/results', [\App\Http\Controllers\Student\ResultController::class, 'index'])->name('results.index');

        Route::get('/accommodation', [AccommodationController::class, 'index'])->name('accommodation.index');
        Route::post('/accommodation', [AccommodationController::class, 'store'])->name('accommodation.store');
        Route::post('/accommodation/cancel-expired', [AccommodationController::class, 'cancelExpired'])->name('accommodation.cancel-expired');
        Route::get('/accommodation/download-slip', [AccommodationController::class, 'downloadAccommodationSlip'])->name('accommodation.download-slip');
        Route::get('/accommodation/download-payment', [AccommodationController::class, 'downloadPaymentSlip'])->name('accommodation.download-payment');

        Route::get('/id-card', [IdCardController::class, 'show'])->name('id_card.show');
        Route::get('/admission-letter', [\App\Http\Controllers\Student\ProfileController::class, 'downloadAdmissionLetter'])->name('admission_letter.download');

        // Library routes
        Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
        Route::post('/library/request', [LibraryController::class, 'requestBook'])->name('library.request');
        Route::get('/library/books/{book}/download', [LibraryController::class, 'downloadEbook'])->name('library.books.download');

        // Sickbay routes
        Route::get('/sickbay', [\App\Http\Controllers\Student\SickbayController::class, 'index'])->name('sickbay.index');

        // Support routes
        Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');

        // Announcements routes
        Route::get('/announcements', [StudentAnnouncementController::class, 'index'])->name('announcements.index');

        // Postgraduate Capstone & Dissertation routes
        Route::get('/research', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'index'])->name('research.index');
        Route::post('/research/topic', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'storeTopic'])->name('research.topic.store');
        Route::post('/research/milestones/{milestone}', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'submitMilestone'])->name('research.milestones.submit');
        Route::post('/research/logs', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'storeLog'])->name('research.logs.store');
        Route::post('/research/progress-reports', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'storeProgressReport'])->name('research.progress-reports.store');
        Route::post('/research/candidacy-requests', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'storeCandidacyRequest'])->name('research.candidacy-requests.store');
        Route::post('/research/publications', [\App\Http\Controllers\Student\PostgraduateResearchController::class, 'storePublication'])->name('research.publications.store');
    });
});
