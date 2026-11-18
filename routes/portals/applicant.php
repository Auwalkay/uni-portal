<?php

use App\Http\Controllers\Applicant\ApplicationController;
use Illuminate\Support\Facades\Route;

// APPLICANT ROUTES
Route::prefix('applicant')->name('applicant.')->middleware(['permission:access_applicant_portal'])->group(function () {
    Route::get('/dashboard', [ApplicationController::class, 'index'])->name('dashboard');
    Route::get('/apply/start', [ApplicationController::class, 'create'])->name('apply.start');
    Route::post('/apply/select-programme', [ApplicationController::class, 'selectProgramme'])->name('apply.select_programme');
    Route::get('/apply/form', [ApplicationController::class, 'form'])->name('apply.form');
    Route::post('/apply/save-step', [ApplicationController::class, 'saveStep'])->name('apply.save_step');
    Route::post('/apply', [ApplicationController::class, 'store'])->name('apply.store');
    Route::get('/application/preview', [ApplicationController::class, 'show'])->name('apply.show');
    Route::get('/application/transcript-label', [ApplicationController::class, 'downloadTranscriptLabel'])->name('apply.transcript_label');

    Route::get('/payment', [\App\Http\Controllers\Applicant\PaymentController::class, 'index'])->name('payment.index');
    Route::post('/payment/pay', [\App\Http\Controllers\Applicant\PaymentController::class, 'pay'])->name('payment.pay');
    Route::get('/payment/callback', [\App\Http\Controllers\Applicant\PaymentController::class, 'callback'])->name('payment.callback');

    Route::post('/accept-offer', [ApplicationController::class, 'acceptOffer'])->name('accept.offer');
});

// External API Simulations
Route::prefix('api/external')->group(function () {
    Route::post('/jamb/fetch', [\App\Http\Controllers\External\JambController::class, 'fetchDetails']);
});
