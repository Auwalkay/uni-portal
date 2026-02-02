<?php

use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'verified', 'role:admin|registrar'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('finance/dashboard', [\App\Http\Controllers\Admin\FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::resource('staff', StaffController::class);
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