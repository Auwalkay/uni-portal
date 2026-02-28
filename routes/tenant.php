<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CourseAllocationController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\SalaryController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Staff\StaffFinanceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenancyServiceProvider.
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    \App\Http\Middleware\CheckTenantIsActive::class,
])->group(function () {

    Route::middleware(['auth', 'verified', 'role:admin|registrar|bursar|finance_officer|receptionist'])->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('finance')->name('finance.')->group(function () {
            Route::get('dashboard', [FinanceController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [FinanceController::class, 'index'])->name('index');

            // Fee Logic
            Route::post('fee-types', [FinanceController::class, 'storeFeeType'])->name('fee_types.store');
            Route::put('fee-types/{feeType}', [FinanceController::class, 'updateFeeType'])->name('fee_types.update');
            Route::delete('fee-types/{feeType}', [FinanceController::class, 'destroyFeeType'])->name('fee_types.destroy');
            Route::post('fee-configurations', [FinanceController::class, 'storeFeeConfiguration'])->name('fee_configurations.store');
            Route::put('fee-configurations/{config}', [FinanceController::class, 'updateFeeConfiguration'])->name('fee_configurations.update');
            Route::delete('fee-configurations/{config}', [FinanceController::class, 'destroyFeeConfiguration'])->name('fee_configurations.destroy');
            Route::get('session/{session}/fees', [FinanceController::class, 'manageSessionFees'])->name('session.fees');

            // Expense Categories
            Route::post('expense-categories', [FinanceController::class, 'storeExpenseCategory'])->name('expense_categories.store');
            Route::put('expense-categories/{category}', [FinanceController::class, 'updateExpenseCategory'])->name('expense_categories.update');
            Route::delete('expense-categories/{category}', [FinanceController::class, 'destroyExpenseCategory'])->name('expense_categories.destroy');

            // Expenses
            Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
            Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
            Route::resource('expenses', ExpenseController::class);

            // Payroll
            Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
            Route::post('payroll/{payroll}/mark-as-paid', [PayrollController::class, 'markAsPaid'])->name('payroll.mark-as-paid');
            Route::get('payroll/{payroll}/payslip/{payrollItem}/download', [PayrollController::class, 'downloadPayslip'])->name('payroll.payslip.download');
            Route::resource('payroll', PayrollController::class)->only(['index', 'show', 'destroy']);

            // Salary
            Route::get('conf/salaries/export', [SalaryController::class, 'export'])->name('salary.export');
            Route::post('conf/salaries/import', [SalaryController::class, 'import'])->name('salary.import');
            Route::get('conf/salaries', [SalaryController::class, 'index'])->name('salary.index');
            Route::put('conf/salaries/{staff}', [SalaryController::class, 'update'])->name('salary.update');
        });
        Route::resource('staff', StaffController::class);
        Route::post('course-allocations/import', [CourseAllocationController::class, 'import'])->name('course-allocations.import');
        Route::get('course-allocations/template', [CourseAllocationController::class, 'downloadTemplate'])->name('course-allocations.template');
        Route::resource('course-allocations', CourseAllocationController::class);
        Route::get('invoices/search-students', [InvoiceController::class, 'searchStudents'])->name('invoices.search-students');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'create', 'store']);
        Route::post('invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
        Route::post('payments/{payment}/verify', [InvoiceController::class, 'verifyPayment'])->name('payments.verify');
    });

    // Staff Self-Service Routes
    Route::middleware(['auth', 'verified', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
        Route::get('payslips', [StaffFinanceController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payrollItem}/download', [StaffFinanceController::class, 'download'])->name('payslips.download');
    });

    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    })->name('home');

    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'registrar', 'dean', 'hod', 'course_coordinator', 'lecturer', 'admissions_manager', 'admissions_officer', 'admissions_clerk', 'bursar', 'finance_officer', 'finance_clerk', 'receptionist'])) {
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
});

require base_path('vendor/laravel/fortify/routes/routes.php');
