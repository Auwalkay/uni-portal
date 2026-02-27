<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\HomeController;
use App\Http\Controllers\Central\DashboardController;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\DomainController;
use App\Http\Controllers\Central\SubscriptionController;
use App\Http\Controllers\Central\CentralUserController;
use App\Http\Controllers\Central\TenantAcademicController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\Central\AuthController;

foreach (config('tenancy.central_domains', []) as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('central.home');

        Route::middleware('guest:central')->group(function () {
            Route::get('/saas/login', [AuthController::class, 'create'])->name('central.login');
            Route::post('/saas/login', [AuthController::class, 'store'])->name('central.login.store');
        });

        Route::middleware(['auth:central'])->group(function () {
            Route::post('/saas/logout', [AuthController::class, 'destroy'])->name('central.logout');

            Route::prefix('saas')->name('central.')->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

                // Tenant Management
                Route::resource('tenants', TenantController::class)->only(['index', 'create', 'store', 'show', 'update']);
                Route::post('tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
                Route::post('tenants/{tenant}/subscriptions', [TenantController::class, 'storeSubscription'])->name('tenants.subscriptions.store');

                // Domain Management
                Route::get('domains', [DomainController::class, 'index'])->name('domains.index');

                // Subscription Management
                Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

                // Platform User Management
                Route::resource('central-users', CentralUserController::class)->except(['create', 'edit', 'show']);

                // Central Academic Management routes for a specific Tenant
                Route::prefix('tenants/{tenant}')->name('tenants.')->group(function () {
                    Route::get('academics', [TenantAcademicController::class, 'index'])->name('academics');
                    Route::post('faculties', [TenantAcademicController::class, 'storeFaculty'])->name('faculties.store');
                    Route::post('departments', [TenantAcademicController::class, 'storeDepartment'])->name('departments.store');
                    Route::post('programmes', [TenantAcademicController::class, 'storeProgramme'])->name('programmes.store');
                    Route::post('academics/upload', [TenantAcademicController::class, 'bulkUpload'])->name('academics.upload');

                    // Student Monitoring
                    Route::get('students/{student}', [TenantController::class, 'showStudent'])->name('students.show');
                });
            });
        });
    });
}
