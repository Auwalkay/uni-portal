<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Finance/Payroll/Index', [
            'payrolls' => Payroll::with('generatedBy')->latest('generated_at')->paginate(10),
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        if (Payroll::where('month', $validated['month'])->where('year', $validated['year'])->exists()) {
            return back()->with('error', 'Payroll for this month already exists.');
        }

        DB::transaction(function () use ($validated) {
            $payroll = Payroll::create([
                'month' => $validated['month'],
                'year' => $validated['year'],
                'total_amount' => 0,
                'status' => 'draft',
                'generated_by' => Auth::id(),
            ]);

            $totalPayrollAmount = 0;
            $staffMembers = Staff::whereNotNull('basic_salary')->where('basic_salary', '>', 0)->get();

            foreach ($staffMembers as $staff) {
                $basic = $staff->basic_salary;
                $allowances = (float) $staff->allowances + (float) $staff->bonuses;
                $deductions = (float) $staff->deductions;
                $net = $basic + $allowances - $deductions;

                PayrollItem::create([
                    'payroll_id' => $payroll->id,
                    'staff_id' => $staff->id,
                    'basic_salary' => $basic,
                    'total_allowances' => $allowances,
                    'total_deductions' => $deductions,
                    'net_salary' => $net,
                    'status' => 'pending',
                ]);

                $totalPayrollAmount += $net;
            }

            $payroll->update(['total_amount' => $totalPayrollAmount]);
        });

        return back()->with('success', 'Payroll generated successfully.');
    }

    public function show(Payroll $payroll)
    {
        return Inertia::render('Admin/Finance/Payroll/Show', [
            'payroll' => $payroll->load('generatedBy'),
            'items' => $payroll->items()->with(['staff.user', 'staff.department'])->paginate(20),
        ]);
    }

    public function markAsPaid(Payroll $payroll)
    {
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Payroll is already marked as paid.');
        }

        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Also update all items status
        $payroll->items()->update(['status' => 'paid']);

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function destroy(Payroll $payroll)
    {
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Cannot delete paid payroll records.');
        }

        $payroll->delete();
        return back()->with('success', 'Payroll record deleted.');
    }

    public function downloadPayslip(Payroll $payroll, PayrollItem $payrollItem)
    {
        // Security check
        if ($payrollItem->payroll_id !== $payroll->id) {
            abort(404);
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
