<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use App\Models\FeeConfiguration;
use App\Models\Session;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Expense;
use App\Models\Payroll;
use App\Models\ExpenseCategory;

class FinanceController extends Controller
{
    public function dashboard()
    {
        // 1. Total Inflow (All successful payments)
        $totalInflow = Payment::where('status', 'success')->sum('amount');

        // 2. Total Outflow (Approved Expenses + Paid Payrolls)
        $totalExpenses = Expense::where('status', 'approved')->sum('amount');
        $totalPayroll = Payroll::where('status', 'paid')->sum('total_amount');
        $totalOutflow = $totalExpenses + $totalPayroll;

        // 3. Net Balance
        $netBalance = $totalInflow - $totalOutflow;

        // 4. Monthly Cash Flow (Last 6 months)
        $data = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->format('Y-m');
            $label = $date->format('M Y');

            $inflow = Payment::where('status', 'success')
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount');

            $outflowExpenses = Expense::where('status', 'approved')
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month) // Assuming 'date' is when expense incurred
                ->sum('amount');

            // For payroll, we can use generated_at or paid_at
            $outflowPayroll = Payroll::where('status', 'paid')
                ->whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('total_amount');

            $data->push([
                'month' => $label,
                'inflow' => $inflow,
                'outflow' => $outflowExpenses + $outflowPayroll,
            ]);
        }

        // 5. Recent Transactions (Mixed Payments and Expenses)
        $recentPayments = Payment::with('user')->latest('paid_at')->take(5)->get()->map(function ($p) {
            return [
                'type' => 'inflow',
                'description' => 'Payment from ' . $p->user->name,
                'amount' => $p->amount,
                'date' => $p->paid_at,
                'status' => 'success'
            ];
        });

        $recentExpenses = Expense::with('user')->latest('date')->take(5)->get()->map(function ($e) {
            return [
                'type' => 'outflow',
                'description' => $e->title . ' (' . $e->user->name . ')',
                'amount' => $e->amount,
                'date' => $e->date,
                'status' => $e->status
            ];
        });

        $recentTransactions = $recentPayments->concat($recentExpenses)->sortByDesc('date')->take(10)->values();

        return Inertia::render('Admin/Finance/Dashboard', [
            'stats' => [
                'totalInflow' => $totalInflow,
                'totalOutflow' => $totalOutflow,
                'netBalance' => $netBalance,
            ],
            'chartData' => $data,
            'recentTransactions' => $recentTransactions
        ]);
    }

    public function index()
    {
        return Inertia::render('Admin/Finance/Index', [
            'feeTypes' => FeeType::withCount('configurations')->get(),
            'expenseCategories' => ExpenseCategory::withCount('expenses')->get(),
            'sessions' => Session::with(['feeConfigurations.feeType', 'feeConfigurations.faculty', 'feeConfigurations.department', 'feeConfigurations.program'])
                ->withCount('feeConfigurations')
                ->orderBy('start_date', 'desc')
                ->get(),
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'programs' => Programme::orderBy('name')->get(),
        ]);
    }

    // ... Fee Type methods ...

    // Expense Category Methods
    public function storeExpenseCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories',
            'description' => 'nullable|string',
        ]);

        ExpenseCategory::create($validated);
        return back()->with('success', 'Expense Category created.');
    }

    public function updateExpenseCategory(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);
        return back()->with('success', 'Expense Category updated.');
    }

    public function destroyExpenseCategory(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            return back()->with('error', 'Cannot delete category with associated expenses.');
        }
        $category->delete();
        return back()->with('success', 'Expense Category deleted.');
    }

    // ... (Keep existing Fee Configuration methods: storeFeeType, updateFeeType, destroyFeeType, storeFeeConfiguration, etc.)
    public function storeFeeType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fee_types',
            'description' => 'nullable|string',
        ]);

        FeeType::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
        ]);

        return back()->with('success', 'Fee Type created successfully.');
    }

    public function updateFeeType(Request $request, FeeType $feeType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fee_types,name,' . $feeType->id,
            'description' => 'nullable|string',
        ]);

        $feeType->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
        ]);

        return back()->with('success', 'Fee Type updated successfully.');
    }

    public function destroyFeeType(FeeType $feeType)
    {
        if ($feeType->configurations()->exists()) {
            return back()->with('error', 'Cannot delete Fee Type with active configurations.');
        }

        $feeType->delete();
        return back()->with('success', 'Fee Type deleted successfully.');
    }

    public function storeFeeConfiguration(Request $request)
    {
        $validated = $request->validate([
            'fee_type_id' => 'required|exists:fee_types,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'amount' => 'required|numeric|min:0',
            'faculty_id' => 'nullable|exists:faculties,id',
            'department_id' => 'nullable|exists:departments,id',
            'program_id' => 'nullable|exists:programmes,id',
            'level' => 'nullable|string', // 100, 200, etc.
            'is_compulsory' => 'boolean',
        ]);

        FeeConfiguration::create($validated);

        return back()->with('success', 'Fee Configuration rules saved.');
    }

    public function updateFeeConfiguration(Request $request, FeeConfiguration $config)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'level' => 'nullable|string',
            'is_compulsory' => 'boolean',
            // Typically we don't allow changing the structural targets (faculty/dept/program) on edit to avoid confusion, 
            // but for flexibility we could. Let's keep it simple for now: only amount/level/compulsory.
        ]);

        $config->update($validated);

        return back()->with('success', 'Fee Configuration updated.');
    }

    public function destroyFeeConfiguration(FeeConfiguration $config)
    {
        $config->delete();
        return back()->with('success', 'Fee Configuration removed.');
    }

    public function manageSessionFees(Session $session)
    {
        return Inertia::render('Admin/Finance/SessionFees', [
            'session' => $session->load(['feeConfigurations.feeType', 'feeConfigurations.faculty', 'feeConfigurations.department', 'feeConfigurations.program']),
            'feeTypes' => FeeType::all(),
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'programs' => Programme::orderBy('name')->get(),
        ]);
    }
}
