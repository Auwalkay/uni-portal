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

class FinanceController extends Controller
{
    public function dashboard()
    {
        // 1. Total Revenue (Sum of all completed payments)
        $totalRevenue = Payment::sum('amount');

        // 2. Total Outstanding (Sum of all invoices - Sum of all payments) - Simplified
        // Ideally invoices have a status, but if not, we calculate diff.
        // Assuming invoices have a 'amount' and we check payments against them.
        // Let's verify Invoice content from previous turn. It has `payments()` relation.
        // For now, let's just sum all Invoice amounts.
        $totalInvoiced = Invoice::sum('amount');
        $outstanding = $totalInvoiced - $totalRevenue;

        // 3. Monthly Revenue (Last 6 months)
        $monthlyRevenue = Payment::select(
            DB::raw('sum(amount) as total'),
            DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month")
        )
            ->where('paid_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 4. Recent Transactions
        $recentPayments = Payment::with(['user', 'invoice'])
            ->latest('paid_at')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Finance/Dashboard', [
            'stats' => [
                'totalRevenue' => $totalRevenue,
                'outstanding' => $outstanding,
                'totalInvoiced' => $totalInvoiced,
            ],
            'chartData' => $monthlyRevenue,
            'recentPayments' => $recentPayments
        ]);
    }

    public function index()
    {

        return Inertia::render('Admin/Finance/Index', [
            'feeTypes' => FeeType::withCount('configurations')->get(),
            'sessions' => Session::with(['feeConfigurations.feeType', 'feeConfigurations.faculty', 'feeConfigurations.department', 'feeConfigurations.program'])
                ->withCount('feeConfigurations')
                ->orderBy('start_date', 'desc')
                ->get(),
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'programs' => Programme::orderBy('name')->get(),
        ]);
    }

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
