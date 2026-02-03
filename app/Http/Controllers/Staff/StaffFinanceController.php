<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class StaffFinanceController extends Controller
{
    /**
     * Display a listing of the authenticated staff member's payslips.
     */
    public function index()
    {
        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            return Inertia::render('Staff/MyPayslips', [
                'payslips' => [],
            ])->with('error', 'Staff record not found.');
        }

        $payslips = PayrollItem::where('staff_id', $staff->id)
            ->with(['payroll'])
            ->join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->orderBy('payrolls.month', 'desc')
            ->orderBy('payrolls.year', 'desc')
            ->select('payroll_items.*')
            ->get();

        return Inertia::render('Staff/MyPayslips', [
            'payslips' => $payslips,
        ]);
    }

    /**
     * Download a specific payslip for the authenticated staff member.
     */
    public function download(PayrollItem $payrollItem)
    {
        $user = Auth::user();
        $staff = $user->staff;

        // Security check: Ensure staff belongs to the record
        if (!$staff || $payrollItem->staff_id !== $staff->id) {
            abort(403, 'Unauthorized access to this payslip.');
        }

        $payrollItem->load(['staff.user', 'staff.department.faculty', 'payroll']);

        $pdf = Pdf::loadView('pdf.payslip', [
            'item' => $payrollItem,
            'staff' => $payrollItem->staff,
            'payroll' => $payrollItem->payroll,
        ]);

        $monthName = date('F', mktime(0, 0, 0, $payrollItem->payroll->month, 10));
        $safeStaffNumber = str_replace(['/', '\\'], '_', $payrollItem->staff->staff_number);
        $filename = "Payslip_{$safeStaffNumber}_{$monthName}_{$payrollItem->payroll->year}.pdf";

        return $pdf->download($filename);
    }
}
