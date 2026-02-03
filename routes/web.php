<?php

use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::middleware(['auth', 'verified', 'role:admin|registrar|bursar|finance_officer'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Admin\FinanceController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('index');

        // Fee Logic
        Route::post('fee-types', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeType'])->name('fee_types.store');
        Route::put('fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeType'])->name('fee_types.update');
        Route::delete('fee-types/{feeType}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeType'])->name('fee_types.destroy');
        Route::post('fee-configurations', [\App\Http\Controllers\Admin\FinanceController::class, 'storeFeeConfiguration'])->name('fee_configurations.store');
        Route::put('fee-configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateFeeConfiguration'])->name('fee_configurations.update');
        Route::delete('fee-configurations/{config}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyFeeConfiguration'])->name('fee_configurations.destroy');
        Route::get('session/{session}/fees', [\App\Http\Controllers\Admin\FinanceController::class, 'manageSessionFees'])->name('session.fees');

        // Expense Categories
        Route::post('expense-categories', [\App\Http\Controllers\Admin\FinanceController::class, 'storeExpenseCategory'])->name('expense_categories.store');
        Route::put('expense-categories/{category}', [\App\Http\Controllers\Admin\FinanceController::class, 'updateExpenseCategory'])->name('expense_categories.update');
        Route::delete('expense-categories/{category}', [\App\Http\Controllers\Admin\FinanceController::class, 'destroyExpenseCategory'])->name('expense_categories.destroy');

        // Expenses
        Route::post('expenses/{expense}/approve', [\App\Http\Controllers\Admin\ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/reject', [\App\Http\Controllers\Admin\ExpenseController::class, 'reject'])->name('expenses.reject');
        Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class);

        // Payroll
        Route::post('payroll/generate', [\App\Http\Controllers\Admin\PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/{payroll}/mark-as-paid', [\App\Http\Controllers\Admin\PayrollController::class, 'markAsPaid'])->name('payroll.mark-as-paid');
        Route::get('payroll/{payroll}/payslip/{payrollItem}/download', [\App\Http\Controllers\Admin\PayrollController::class, 'downloadPayslip'])->name('payroll.payslip.download');
        Route::resource('payroll', \App\Http\Controllers\Admin\PayrollController::class)->only(['index', 'show', 'destroy']);

        // Salary
        Route::get('conf/salaries/export', [\App\Http\Controllers\Admin\SalaryController::class, 'export'])->name('salary.export');
        Route::post('conf/salaries/import', [\App\Http\Controllers\Admin\SalaryController::class, 'import'])->name('salary.import');
        Route::get('conf/salaries', [\App\Http\Controllers\Admin\SalaryController::class, 'index'])->name('salary.index');
        Route::put('conf/salaries/{staff}', [\App\Http\Controllers\Admin\SalaryController::class, 'update'])->name('salary.update');
    });
    Route::resource('staff', StaffController::class);
    Route::post('course-allocations/import', [\App\Http\Controllers\Admin\CourseAllocationController::class, 'import'])->name('course-allocations.import');
    Route::get('course-allocations/template', [\App\Http\Controllers\Admin\CourseAllocationController::class, 'downloadTemplate'])->name('course-allocations.template');
    Route::resource('course-allocations', \App\Http\Controllers\Admin\CourseAllocationController::class);
    Route::get('invoices/search-students', [\App\Http\Controllers\Admin\InvoiceController::class, 'searchStudents'])->name('invoices.search-students');
    Route::resource('invoices', \App\Http\Controllers\Admin\InvoiceController::class)->only(['index', 'show', 'create', 'store']);
    Route::post('invoices/{invoice}/mark-as-paid', [\App\Http\Controllers\Admin\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
    Route::post('payments/{payment}/verify', [\App\Http\Controllers\Admin\InvoiceController::class, 'verifyPayment'])->name('payments.verify');
});

// Staff Self-Service Routes
Route::middleware(['auth', 'verified', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('payslips', [\App\Http\Controllers\Staff\StaffFinanceController::class, 'index'])->name('payslips.index');
    Route::get('payslips/{payrollItem}/download', [\App\Http\Controllers\Staff\StaffFinanceController::class, 'download'])->name('payslips.download');
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