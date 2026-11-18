<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\FrontDesk\ComplaintController;
use App\Http\Controllers\Admin\FrontDesk\DashboardController;
use App\Http\Controllers\Admin\FrontDesk\EnquiryController;
use App\Http\Controllers\Admin\FrontDesk\VisitorController;
use App\Http\Controllers\Admin\HostelBlockController;
use App\Http\Controllers\Admin\HostelBookingController;
use App\Http\Controllers\Admin\HostelController;
use App\Http\Controllers\Admin\HostelFeeController;
use App\Http\Controllers\Admin\HostelFloorController;
use App\Http\Controllers\Admin\HostelRoomController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ScholarshipController;
use App\Http\Controllers\Admin\SickbayController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Front Desk Module
Route::middleware(['permission:manage_visitors'])->prefix('front-desk')->name('front-desk.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('visitors', VisitorController::class);
    Route::resource('complaints', ComplaintController::class);
    Route::resource('enquiries', EnquiryController::class);
});

// Finance & Payments
Route::middleware(['permission:view_payments'])->group(function () {
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/download', [\App\Http\Controllers\Admin\PaymentController::class, 'downloadReceipt'])->name('payments.download_receipt');
    Route::get('/finance', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index');

    // Restricted Finance Actions
    Route::middleware(['permission:verify_payments'])->group(function () {
        Route::resource('/scholarships', ScholarshipController::class)
            ->except(['create', 'edit', 'show'])
            ->names('scholarships');
    });
});

// Campus Buildings Management
Route::middleware(['permission:view_buildings|manage_buildings'])->group(function () {
    Route::post('buildings/import', [\App\Http\Controllers\Admin\BuildingController::class, 'import'])->name('buildings.import');
    Route::get('buildings/template', [\App\Http\Controllers\Admin\BuildingController::class, 'downloadTemplate'])->name('buildings.template');
    Route::post('buildings/{building}/toggle-status', [\App\Http\Controllers\Admin\BuildingController::class, 'toggleStatus'])->name('buildings.toggle_status');
    Route::post('buildings/{building}/toggle-exam-status', [\App\Http\Controllers\Admin\BuildingController::class, 'toggleExamStatus'])->name('buildings.toggle_exam_status');
    Route::resource('buildings', \App\Http\Controllers\Admin\BuildingController::class)->except(['create', 'show', 'edit']);
});

// Hostel Management
Route::middleware(['permission:manage_hostels|manage_hostel_fees|manage_hostel_bookings|view_hostel_bookings|view_male_hostel_bookings|view_female_hostel_bookings|change_hostel_room'])->group(function () {
    Route::get('hostels/bookings', [HostelBookingController::class, 'index'])->name('hostels.bookings.index');
    Route::get('hostels/bookings/export', [HostelBookingController::class, 'export'])->name('hostels.bookings.export');
    Route::post('hostels/bookings', [HostelBookingController::class, 'store'])->name('hostels.bookings.store');
    Route::post('hostels/bookings/{booking}/unbook', [HostelBookingController::class, 'unbook'])->name('hostels.bookings.unbook');
    Route::post('hostels/bookings/{booking}/reallocate', [HostelBookingController::class, 'reallocate'])->name('hostels.bookings.reallocate');
    Route::post('hostels/bookings/{booking}/change-room', [HostelBookingController::class, 'changeRoom'])->name('hostels.bookings.change-room')->middleware('permission:change_hostel_room');
    Route::get('hostels/bookings/{booking}/download-slip', [HostelBookingController::class, 'downloadSlip'])->name('hostels.bookings.download-slip');
    Route::get('hostels/search-students', [HostelBookingController::class, 'searchStudents'])->name('hostels.search-students');
    Route::get('hostels/rooms/available', [HostelBookingController::class, 'getAvailableRooms'])->name('hostels.rooms.available');

    // Hostel Room Excel Import
    Route::get('hostels/rooms/import-template', [HostelController::class, 'downloadRoomImportTemplate'])->name('hostels.rooms.import-template');
    Route::post('hostels/rooms/import', [HostelController::class, 'importRooms'])->name('hostels.rooms.import');
    Route::post('hostels/{hostel}/rooms/import', [HostelController::class, 'importRooms'])->name('hostels.specific-rooms.import');

    // Hostels CRUD
    Route::get('hostels/create', [HostelController::class, 'create'])->name('hostels.create')->middleware('permission:create_hostels');
    Route::get('hostels/{hostel}/edit', [HostelController::class, 'edit'])->name('hostels.edit')->middleware('permission:create_hostels');
    Route::resource('hostels', HostelController::class)->only(['index', 'show']);
    Route::post('hostels', [HostelController::class, 'store'])->name('hostels.store')->middleware('permission:create_hostels');
    Route::put('hostels/{hostel}', [HostelController::class, 'update'])->name('hostels.update')->middleware('permission:create_hostels');
    Route::delete('hostels/{hostel}', [HostelController::class, 'destroy'])->name('hostels.destroy')->middleware('permission:create_hostels');

    Route::post('hostels/{hostel}/toggle-visibility', [HostelController::class, 'toggleVisibility'])->name('hostels.toggle-visibility')->middleware('permission:toggle_hostels');

    Route::post('hostels/{hostel}/blocks', [HostelBlockController::class, 'store'])->name('hostels.blocks.store')->middleware('permission:create_hostels');
    Route::post('hostels/{hostel}/blocks/{block}/toggle-visibility', [HostelBlockController::class, 'toggleVisibility'])->name('hostels.blocks.toggle-visibility')->middleware('permission:toggle_hostels');
    Route::delete('hostels/{hostel}/blocks/{block}', [HostelBlockController::class, 'destroy'])->name('hostels.blocks.destroy')->middleware('permission:create_hostels');

    Route::post('hostels/{hostel}/blocks/{block}/floors', [HostelFloorController::class, 'store'])->name('hostels.floors.store')->middleware('permission:create_hostels');
    Route::post('hostels/{hostel}/blocks/{block}/floors/{floor}/toggle-visibility', [HostelFloorController::class, 'toggleVisibility'])->name('hostels.floors.toggle-visibility')->middleware('permission:toggle_hostels');
    Route::delete('hostels/{hostel}/blocks/{block}/floors/{floor}', [HostelFloorController::class, 'destroy'])->name('hostels.floors.destroy')->middleware('permission:create_hostels');

    Route::post('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms', [HostelRoomController::class, 'store'])->name('hostels.rooms.store')->middleware('permission:create_hostels');
    Route::put('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms/{room}', [HostelRoomController::class, 'update'])->name('hostels.rooms.update')->middleware('permission:create_hostels');
    Route::delete('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms/{room}', [HostelRoomController::class, 'destroy'])->name('hostels.rooms.destroy')->middleware('permission:create_hostels');

    Route::post('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms/{room}/toggle-visibility', [HostelRoomController::class, 'toggleVisibility'])->name('hostels.rooms.toggle-visibility')->middleware('permission:toggle_hostels');
    Route::post('hostels/{hostel}/blocks/{block}/floors/{floor}/rooms/{room}/toggle-suspension', [HostelRoomController::class, 'toggleSuspension'])->name('hostels.rooms.toggle-suspension')->middleware('permission:toggle_hostels');
});

// Hostel Fees Management
Route::middleware(['permission:manage_hostel_fees'])->group(function () {
    Route::post('hostels/fees', [HostelFeeController::class, 'store'])->name('hostels.fees.store');
    Route::put('hostels/fees/{fee}', [HostelFeeController::class, 'update'])->name('hostels.fees.update');
    Route::delete('hostels/fees/{fee}', [HostelFeeController::class, 'destroy'])->name('hostels.fees.destroy');
});

// System Settings & RBAC Management
Route::middleware(['permission:manage_system_settings'])->group(function () {
    Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/update', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateSetting'])->name('settings.update');

    Route::get('/settings/roles', [RoleController::class, 'index'])->name('settings.roles.index');
    Route::get('/settings/roles/create', [RoleController::class, 'create'])->name('settings.roles.create');
    Route::post('/settings/roles', [RoleController::class, 'store'])->name('settings.roles.store');
    Route::get('/settings/roles/{role}/edit', [RoleController::class, 'edit'])->name('settings.roles.edit');
    Route::put('/settings/roles/{role}', [RoleController::class, 'update'])->name('settings.roles.update');
});

// Audit Logs Route
Route::middleware(['permission:manage_system_settings|view_audit_logs|view_activity_logs'])->group(function () {
    Route::get('/settings/logs', [ActivityLogController::class, 'index'])->name('settings.logs.index');
});

// System Users Module
Route::middleware(['permission:manage_system_settings|manage_users'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status.toggle');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
});

// Library Management
Route::middleware(['permission:view_library'])->group(function () {
    Route::get('/library', [\App\Http\Controllers\Admin\LibraryController::class, 'index'])->name('library.index');
    Route::post('/library/books', [\App\Http\Controllers\Admin\LibraryController::class, 'store'])->name('library.books.store')->middleware('permission:manage_library_books');
    Route::post('/library/books/{book}', [\App\Http\Controllers\Admin\LibraryController::class, 'update'])->name('library.books.update')->middleware('permission:manage_library_books');
    Route::delete('/library/books/{book}', [\App\Http\Controllers\Admin\LibraryController::class, 'destroy'])->name('library.books.destroy')->middleware('permission:manage_library_books');
    Route::post('/library/categories', [\App\Http\Controllers\Admin\LibraryController::class, 'storeCategory'])->name('library.categories.store')->middleware('permission:manage_library_books');

    // Loan Actions
    Route::post('/library/loans/{loan}/approve', [\App\Http\Controllers\Admin\LibraryController::class, 'approveLoan'])->name('library.loans.approve')->middleware('permission:manage_library_borrows');
    Route::post('/library/loans/{loan}/reject', [\App\Http\Controllers\Admin\LibraryController::class, 'rejectLoan'])->name('library.loans.reject')->middleware('permission:manage_library_borrows');
    Route::post('/library/loans/{loan}/return', [\App\Http\Controllers\Admin\LibraryController::class, 'returnBook'])->name('library.loans.return')->middleware('permission:manage_library_borrows');
});

// Sickbay Management
Route::middleware(['permission:view_sickbay_portal'])->group(function () {
    Route::get('/sickbay', [SickbayController::class, 'index'])->name('sickbay.index');
    Route::get('/sickbay/beds', [SickbayController::class, 'bedsIndex'])->name('sickbay.beds.index');
    Route::get('/sickbay/logs', [SickbayController::class, 'logsIndex'])->name('sickbay.logs.index');
    Route::get('/sickbay/supplies', [SickbayController::class, 'suppliesIndex'])->name('sickbay.supplies.index');
    Route::get('/sickbay/patients', [SickbayController::class, 'patientsIndex'])->name('sickbay.patients.index');
    Route::get('/sickbay/reports', [SickbayController::class, 'reportsIndex'])->name('sickbay.reports.index');
    Route::get('/sickbay/visits/{visit}/prescription', [SickbayController::class, 'prescriptionSlip'])->name('sickbay.prescription');

    Route::get('/sickbay/students/search', [SickbayController::class, 'searchStudents'])->name('sickbay.students.search')->middleware('permission:register_walk_in');
    Route::post('/sickbay/check-in', [SickbayController::class, 'registerPatient'])->name('sickbay.check_in')->middleware('permission:register_walk_in');
    Route::post('/sickbay/treatment/{visit}', [SickbayController::class, 'updateVitalsAndTreatment'])->name('sickbay.treatment.store')->middleware('permission:write_sickbay_medical_logs');
    Route::post('/sickbay/beds/{visit}/assign', [SickbayController::class, 'assignBed'])->name('sickbay.beds.assign')->middleware('permission:manage_observation_beds');
    Route::post('/sickbay/beds/{visit}/discharge', [SickbayController::class, 'dischargeBed'])->name('sickbay.beds.discharge')->middleware('permission:manage_observation_beds');
    Route::post('/sickbay/inventory', [SickbayController::class, 'storeInventory'])->name('sickbay.inventory.store')->middleware('permission:manage_sickbay_inventory');
    Route::get('/sickbay/patients/{user}/history', [SickbayController::class, 'patientHistory'])->name('sickbay.patient_history');
    Route::post('/sickbay/beds', [SickbayController::class, 'storeBed'])->name('sickbay.beds.store')->middleware('permission:manage_observation_beds');
});

// Bulk Communications
Route::middleware(['permission:manage_bulk_communications'])->group(function () {
    Route::get('/announcements', [\App\Http\Controllers\Admin\CommunicationController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [\App\Http\Controllers\Admin\CommunicationController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [\App\Http\Controllers\Admin\CommunicationController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{bulletin}/edit', [\App\Http\Controllers\Admin\CommunicationController::class, 'edit'])->name('announcements.edit');
    Route::post('/announcements/{bulletin}', [\App\Http\Controllers\Admin\CommunicationController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{bulletin}', [\App\Http\Controllers\Admin\CommunicationController::class, 'destroy'])->name('announcements.destroy');
});
