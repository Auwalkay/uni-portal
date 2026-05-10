<?php

use App\Http\Controllers\Api\DeviceAttendanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Biometric Device Integration
Route::post('/attendance/device', [DeviceAttendanceController::class, 'sync']);
