<?php

use App\Http\Controllers\Admin\SickbayController;
use Illuminate\Support\Facades\Route;

// Academic Staff Postgraduate Supervisory Workbench
Route::middleware(['auth', 'verified', 'permission:supervise_pg_students|manage_postgraduate_school'])->prefix('staff/postgraduate')->name('staff.postgraduate.')->group(function () {
    Route::get('/supervision', [\App\Http\Controllers\Staff\PostgraduateSupervisionController::class, 'index'])->name('supervision.index');
    Route::post('/supervision/logs/{log}/review', [\App\Http\Controllers\Staff\PostgraduateSupervisionController::class, 'reviewLog'])->name('supervision.logs.review');
    Route::post('/supervision/milestones/{milestone}/review', [\App\Http\Controllers\Staff\PostgraduateSupervisionController::class, 'reviewMilestone'])->name('supervision.milestones.review');
    Route::post('/supervision/progress-reports/{progressReport}/endorse', [\App\Http\Controllers\Staff\PostgraduateSupervisionController::class, 'endorseProgressReport'])->name('supervision.progress-reports.endorse');
});

// Admin/Staff Personal History Routes (accessible by all staff and admin users with view_library / view_sickbay_portal)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['permission:view_library'])->group(function () {
        Route::get('/library/history', [\App\Http\Controllers\Admin\LibraryController::class, 'history'])->name('library.history');
        Route::post('/library/history/request', [\App\Http\Controllers\Admin\LibraryController::class, 'requestBook'])->name('library.history.request');
        Route::get('/library/books/{book}/download', [\App\Http\Controllers\Admin\LibraryController::class, 'downloadEbook'])->name('library.books.download');
    });

    Route::middleware(['permission:view_sickbay_portal'])->group(function () {
        Route::get('/sickbay/history', [SickbayController::class, 'history'])->name('sickbay.history');
    });
});
