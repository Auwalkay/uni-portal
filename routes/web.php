<?php

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

Route::middleware(['auth', 'verified', 'permission:access_admin_dashboard'])->prefix('admin')->name('admin.')->group(function () {
    
    // FINANCE MODULE
    Route::prefix('finance')->name('finance.')->group(function () {
        
        // General Finance View
        Route::middleware(['permission:view_payments'])->group(function () {
            Route::get('dashboard', [FinanceController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [FinanceController::class, 'index'])->name('index');
        });

        // Fee Management (Requires manage_payments)
        Route::middleware(['permission:manage_payments'])->group(function () {
            Route::post('fee-types', [FinanceController::class, 'storeFeeType'])->name('fee_types.store');
            Route::put('fee-types/{feeType}', [FinanceController::class, 'updateFeeType'])->name('fee_types.update');
            Route::delete('fee-types/{feeType}', [FinanceController::class, 'destroyFeeType'])->name('fee_types.destroy');
            Route::post('fee-configurations', [FinanceController::class, 'storeFeeConfiguration'])->name('fee_configurations.store');
            Route::put('fee-configurations/{config}', [FinanceController::class, 'updateFeeConfiguration'])->name('fee_configurations.update');
            Route::delete('fee-configurations/{config}', [FinanceController::class, 'destroyFeeConfiguration'])->name('fee_configurations.destroy');
            Route::get('session/{session}/fees', [FinanceController::class, 'manageSessionFees'])->name('session.fees');
            Route::post('clone-fees', [FinanceController::class, 'cloneSessionFees'])->name('clone_fees');
        });

        // Expense Management (Requires view_expenses)
        Route::middleware(['permission:view_expenses'])->group(function () {
            Route::post('expense-categories', [FinanceController::class, 'storeExpenseCategory'])->name('expense_categories.store');
            Route::put('expense-categories/{category}', [FinanceController::class, 'updateExpenseCategory'])->name('expense_categories.update');
            Route::delete('expense-categories/{category}', [FinanceController::class, 'destroyExpenseCategory'])->name('expense_categories.destroy');
            
            Route::resource('expenses', ExpenseController::class);
            
            // Approval Actions (Requires approve_expenses)
            Route::middleware(['permission:approve_expenses'])->group(function () {
                Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
                Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
            });
        });

        // Payroll Management (Requires run_payroll)
        Route::middleware(['permission:run_payroll'])->group(function () {
            Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
            Route::post('payroll/{payroll}/mark-as-paid', [PayrollController::class, 'markAsPaid'])->name('payroll.mark-as-paid');
            Route::get('payroll/{payroll}/payslip/{payrollItem}/download', [PayrollController::class, 'downloadPayslip'])->name('payroll.payslip.download');
            Route::resource('payroll', PayrollController::class)->only(['index', 'show', 'destroy']);
        });

        // Salary Management (Requires view_salaries)
        Route::middleware(['permission:view_salaries'])->group(function () {
            Route::get('conf/salaries/export', [SalaryController::class, 'export'])->name('salary.export');
            Route::post('conf/salaries/import', [SalaryController::class, 'import'])->name('salary.import');
            Route::get('conf/salaries', [SalaryController::class, 'index'])->name('salary.index');
            Route::put('conf/salaries/{staff}', [SalaryController::class, 'update'])->name('salary.update');
        });

        // Bursary Reports
        Route::middleware(['permission:view_bursary_reports'])->group(function () {
            Route::get('bursary/student-fees', [\App\Http\Controllers\Admin\BursaryController::class, 'studentFeesReport'])->name('bursary.student-fees');
            Route::get('bursary/student-fees/export', [\App\Http\Controllers\Admin\BursaryController::class, 'exportExcel'])->name('bursary.student-fees.export');
            Route::get('bursary/student-fees/pdf', [\App\Http\Controllers\Admin\BursaryController::class, 'exportPDF'])->name('bursary.student-fees.pdf');
        });
    });

    // STAFF MANAGEMENT
    Route::middleware(['permission:view_staff'])->group(function () {
        
        Route::middleware(['permission:manage_staff'])->group(function () {
            Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        });

        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('staff/export', [StaffController::class, 'export'])->name('staff.export');
        Route::get('staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
        
        Route::middleware(['permission:manage_staff'])->group(function () {
            Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
            Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
            
            Route::post('staff/import', [StaffController::class, 'import'])->name('staff.import');
            Route::get('staff/template', [StaffController::class, 'downloadTemplate'])->name('staff.template');
            Route::post('staff/resend-all', [StaffController::class, 'resendAllCredentials'])->name('staff.resend_all');
            Route::post('staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset_password');
            
            Route::resource('designations', \App\Http\Controllers\Admin\DesignationController::class)->except(['create', 'edit', 'show']);
        });

        // Staff Attendance
        Route::middleware(['permission:view_attendance'])->group(function () {
            Route::get('attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('attendance/reports', [\App\Http\Controllers\Admin\AttendanceController::class, 'reports'])->name('attendance.reports');
            Route::get('attendance/export', [\App\Http\Controllers\Admin\AttendanceController::class, 'exportReport'])->name('attendance.export');
            Route::get('attendance/calendar', [\App\Http\Controllers\Admin\AttendanceController::class, 'calendar'])->name('attendance.calendar');
            Route::get('attendance/download-template', [\App\Http\Controllers\Admin\AttendanceController::class, 'downloadTemplate'])->name('attendance.download-template');
            
            Route::middleware(['permission:manage_attendance'])->group(function () {
                Route::post('attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('attendance.store');
                Route::post('attendance/import', [\App\Http\Controllers\Admin\AttendanceController::class, 'import'])->name('attendance.import');
                Route::post('attendance/holidays', [\App\Http\Controllers\Admin\AttendanceController::class, 'storeHoliday'])->name('attendance.holiday.store');
                Route::delete('attendance/holidays/{holiday}', [\App\Http\Controllers\Admin\AttendanceController::class, 'destroyHoliday'])->name('attendance.holiday.destroy');
                Route::delete('attendance/{attendance}', [\App\Http\Controllers\Admin\AttendanceController::class, 'destroy'])->name('attendance.destroy');
            });
        });
    });


    // COURSE ALLOCATIONS
    Route::middleware(['permission:assign_coordinators'])->group(function () {
        Route::post('course-allocations/import', [CourseAllocationController::class, 'import'])->name('course-allocations.import');
        Route::get('course-allocations/template', [CourseAllocationController::class, 'downloadTemplate'])->name('course-allocations.template');
        Route::resource('course-allocations', CourseAllocationController::class);
    });

    // INVOICES & PAYMENTS
    Route::middleware(['permission:view_payments'])->group(function () {
        Route::get('invoices/search-students', [InvoiceController::class, 'searchStudents'])->name('invoices.search-students');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'create', 'store', 'destroy']);
        
        Route::middleware(['permission:manage_payments'])->group(function () {
            Route::post('invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-as-paid');
            Route::post('payments/{payment}/verify', [InvoiceController::class, 'verifyPayment'])->name('payments.verify');
        });
    });

    // INVENTORY MANAGEMENT
    Route::middleware(['permission:view_inventory'])->group(function () {
        Route::get('inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/staff/search', [\App\Http\Controllers\Admin\InventoryAssignmentController::class, 'searchStaff'])->name('inventory.staff.search');
        Route::get('inventory/export', [\App\Http\Controllers\Admin\InventoryController::class, 'export'])->name('inventory.export');
        Route::get('inventory/export-assignments', [\App\Http\Controllers\Admin\InventoryController::class, 'exportAssignments'])->name('inventory.export-assignments');
        Route::get('inventory/complaints', [\App\Http\Controllers\Admin\InventoryComplaintController::class, 'index'])->name('inventory.complaints.index');
        
        Route::middleware(['permission:manage_inventory'])->group(function () {
            Route::post('inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'store'])->name('inventory.store');
            Route::put('inventory/{item}', [\App\Http\Controllers\Admin\InventoryController::class, 'update'])->name('inventory.update');
            Route::delete('inventory/{item}', [\App\Http\Controllers\Admin\InventoryController::class, 'destroy'])->name('inventory.destroy');
            Route::post('inventory/import', [\App\Http\Controllers\Admin\InventoryController::class, 'import'])->name('inventory.import');
            Route::post('inventory/categories', [\App\Http\Controllers\Admin\InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
            
            // Assignments
            Route::post('inventory/assignments', [\App\Http\Controllers\Admin\InventoryAssignmentController::class, 'store'])->name('inventory.assignments.store');
            Route::put('inventory/assignments/{assignment}/return', [\App\Http\Controllers\Admin\InventoryAssignmentController::class, 'returnItem'])->name('inventory.assignments.return');
            
            // Complaints
            Route::put('inventory/complaints/{complaint}', [\App\Http\Controllers\Admin\InventoryComplaintController::class, 'update'])->name('inventory.complaints.update');
        });
    });

    // SUPPORT TICKETS (Admin)
    Route::get('support-tickets', [\App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('support.index');
    Route::get('support-tickets/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('support.show');
    Route::put('support-tickets/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'update'])->name('support.update');
    Route::post('support-tickets/{ticket}/reply', [\App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('support.reply');

});

// Staff Self-Service Routes
Route::middleware(['auth', 'verified', 'permission:access_staff_portal'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('payslips', [StaffFinanceController::class, 'index'])->name('payslips.index');
    Route::get('payslips/{payrollItem}/download', [StaffFinanceController::class, 'download'])->name('payslips.download');
    
    // Inventory
    Route::get('inventory', [\App\Http\Controllers\Staff\MyInventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/complaints', [\App\Http\Controllers\Staff\MyInventoryController::class, 'storeComplaint'])->name('inventory.complaints.store');
});

// Staff Profile (Available to all authenticated users with staff records)
Route::middleware(['auth', 'verified'])->prefix('staff-portal')->name('staff.')->group(function () {
    Route::get('profile', [\App\Http\Controllers\Staff\StaffProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [\App\Http\Controllers\Staff\StaffProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [\App\Http\Controllers\Staff\StaffProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('profile/preview', [\App\Http\Controllers\Staff\StaffProfileController::class, 'show'])->name('profile.show');
});

// Support Tickets (User)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('support', [\App\Http\Controllers\SupportTicketController::class, 'index'])->name('support.index');
    Route::post('support', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('support.store');
    Route::get('support/{ticket}', [\App\Http\Controllers\SupportTicketController::class, 'show'])->name('support.show');
    Route::post('support/{ticket}/reply', [\App\Http\Controllers\SupportTicketController::class, 'reply'])->name('support.reply');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user->can('access_admin_dashboard')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->can('access_staff_portal')) {
        return redirect()->route('staff.payslips.index');
    }

    if ($user->can('access_student_portal')) {
        return redirect()->route('student.dashboard');
    }

    if ($user->can('access_applicant_portal')) {
        return redirect()->route('applicant.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

// Webhooks
Route::post('webhooks/squadco', [\App\Http\Controllers\Webhooks\SquadcoWebhookController::class, 'handle'])->name('webhooks.squadco');
Route::post('webhooks/paystack', [\App\Http\Controllers\Webhooks\PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');

// Public Verification
Route::get('verify-admission/{identifier}', [\App\Http\Controllers\Public\AdmissionVerificationController::class, 'verify'])->name('verify.admission');
