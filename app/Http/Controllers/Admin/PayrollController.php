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

use App\Models\Attendance;

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
                $net = max(0, $basic + $allowances - $deductions);

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

    public function show(Payroll $payroll, Request $request)
    {
        $query = $payroll->items()->with(['staff.user', 'staff.department']);

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('staff.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('staff', function ($sq) use ($search) {
                    $sq->where('staff_number', 'like', "%{$search}%");
                });
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'ALL_STATUS') {
            $query->where('status', $request->status);
        }

        // Per Page
        $perPage = $request->integer('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $items = $query->paginate($perPage)->withQueryString();

        // Fetch attendance stats for all staff members in this payroll month/year
        $month = $payroll->month;
        $year = $payroll->year;

        $allStaffIds = $payroll->items()->pluck('staff_id')->filter()->unique()->toArray();
        $attendanceStats = [];
        $totalAbsentDaysCount = 0;
        $totalLateDaysCount = 0;

        if (!empty($allStaffIds)) {
            $stats = Attendance::whereIn('staff_id', $allStaffIds)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->selectRaw('staff_id, 
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days')
                ->groupBy('staff_id')
                ->get()
                ->keyBy('staff_id');

            foreach ($allStaffIds as $sid) {
                $absent = (int) ($stats[$sid]->absent_days ?? 0);
                $late = (int) ($stats[$sid]->late_days ?? 0);
                $present = (int) ($stats[$sid]->present_days ?? 0);

                $attendanceStats[$sid] = [
                    'absent_days' => $absent,
                    'late_days' => $late,
                    'present_days' => $present,
                ];

                $totalAbsentDaysCount += $absent;
                $totalLateDaysCount += $late;
            }
        }

        // Compute overall financial analysis summary across all payroll items for this payroll
        $summary = [
            'gross_basic' => (float) $payroll->items()->where('status', '!=', 'excluded')->sum('basic_salary'),
            'total_allowances' => (float) $payroll->items()->where('status', '!=', 'excluded')->sum('total_allowances'),
            'total_deductions' => (float) $payroll->items()->where('status', '!=', 'excluded')->sum('total_deductions'),
            'total_net_payable' => (float) $payroll->total_amount,
            'total_staff' => (int) $payroll->items()->count(),
            'active_staff' => (int) $payroll->items()->where('status', '!=', 'excluded')->count(),
            'excluded_staff' => (int) $payroll->items()->where('status', 'excluded')->count(),
            'total_absent_days' => $totalAbsentDaysCount,
            'total_late_days' => $totalLateDaysCount,
        ];

        return Inertia::render('Admin/Finance/Payroll/Show', [
            'payroll' => $payroll->load('generatedBy'),
            'items' => $items,
            'attendanceStats' => $attendanceStats,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    public function updateItem(Payroll $payroll, PayrollItem $payrollItem, Request $request)
    {
        if ($payrollItem->payroll_id !== $payroll->id) {
            abort(404);
        }

        if ($payroll->status === 'paid') {
            return back()->with('error', 'Cannot modify payroll items for a paid payroll.');
        }

        $validated = $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'status' => 'required|in:pending,excluded,paid',
            'allowances' => 'nullable|array',
            'allowances.*.label' => 'required|string|max:100',
            'allowances.*.amount' => 'required|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.label' => 'required|string|max:100',
            'deductions.*.amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
        ]);

        $allowanceBreakdown = [];
        $totalAllowances = 0;
        if (!empty($validated['allowances'])) {
            foreach ($validated['allowances'] as $al) {
                $label = trim($al['label']);
                $amt = (float) $al['amount'];
                if ($label !== '' && $amt >= 0) {
                    $allowanceBreakdown[$label] = $amt;
                    $totalAllowances += $amt;
                }
            }
        }

        $deductionBreakdown = [];
        $totalDeductions = 0;
        if (!empty($validated['deductions'])) {
            foreach ($validated['deductions'] as $dd) {
                $label = trim($dd['label']);
                $amt = (float) $dd['amount'];
                if ($label !== '' && $amt >= 0) {
                    $deductionBreakdown[$label] = $amt;
                    $totalDeductions += $amt;
                }
            }
        }

        $payrollItem->basic_salary = $validated['basic_salary'];
        $payrollItem->total_allowances = $totalAllowances;
        $payrollItem->total_deductions = $totalDeductions;
        $payrollItem->allowance_breakdown = $allowanceBreakdown;
        $payrollItem->deduction_breakdown = $deductionBreakdown;
        $payrollItem->status = $validated['status'];
        $payrollItem->remarks = $validated['remarks'] ?? null;

        $payrollItem->recalculate();
        $payrollItem->save();

        $payroll->recalculateTotal();

        return back()->with('success', 'Staff salary details and deductions updated.');
    }

    public function toggleExclusion(Payroll $payroll, PayrollItem $payrollItem)
    {
        if ($payrollItem->payroll_id !== $payroll->id) {
            abort(404);
        }

        if ($payroll->status === 'paid') {
            return back()->with('error', 'Cannot modify payroll items for a paid payroll.');
        }

        $payrollItem->status = ($payrollItem->status === 'excluded') ? 'pending' : 'excluded';
        $payrollItem->recalculate();
        $payrollItem->save();

        $payroll->recalculateTotal();

        $statusMsg = $payrollItem->status === 'excluded' ? 'Staff excluded from payroll.' : 'Staff included in payroll.';
        return back()->with('success', $statusMsg);
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
