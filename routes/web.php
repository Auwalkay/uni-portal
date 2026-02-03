<?php

use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'verified', 'role:admin|registrar|bursar|finance_officer'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('finance/dashboard', [\App\Http\Controllers\Admin\FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::resource('staff', StaffController::class);
    Route::post('course-allocations/import', [\App\Http\Controllers\Admin\CourseAllocationController::class, 'import'])->name('course-allocations.import');
    Route::get('course-allocations/template', [\App\Http\Controllers\Admin\CourseAllocationController::class, 'downloadTemplate'])->name('course-allocations.template');
    Route::resource('course-allocations', \App\Http\Controllers\Admin\CourseAllocationController::class);
    Route::get('invoices/search-students', [\App\Http\Controllers\Admin\InvoiceController::class, 'searchStudents'])->name('invoices.search-students');
    Route::resource('invoices', \App\Http\Controllers\Admin\InvoiceController::class)->only(['index', 'show', 'create', 'store']);
    Route::post('invoices/{invoice}/mark-as-paid', [\App\Http\Controllers\Admin\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
    Route::post('payments/{payment}/verify', [\App\Http\Controllers\Admin\InvoiceController::class, 'verifyPayment'])->name('payments.verify');
});




Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user->hasAnyRole(['admin', 'registrar', 'dean', 'hod', 'course_coordinator', 'lecturer', 'admissions_manager', 'admissions_officer', 'admissions_clerk', 'bursar', 'finance_officer', 'finance_clerk'])) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('student')) {
        return redirect()->route('student.dashboard');
    }

    if ($user->hasRole('applicant')) {
        return redirect()->route('applicant.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';