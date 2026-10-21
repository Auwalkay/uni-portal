<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $this->getFilteredQuery($request, $user);

        // Clamped Per-Page Pagination
        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        // KPI Summary Statistics for Accessible Scope
        $statsBaseQuery = Expense::query();
        if (!$user->can('request_expenses_for_others') && !$user->can('approve_expenses')) {
            $statsBaseQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('requested_by', $user->id);
            });
        }

        $now = now();
        $stats = [
            'total_approved_amount' => (float) (clone $statsBaseQuery)->where('status', 'approved')->sum('amount'),
            'pending_count' => (int) (clone $statsBaseQuery)->where('status', 'pending')->count(),
            'pending_amount' => (float) (clone $statsBaseQuery)->where('status', 'pending')->sum('amount'),
            'this_month_approved_amount' => (float) (clone $statsBaseQuery)
                ->where('status', 'approved')
                ->whereBetween('date', [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()])
                ->sum('amount'),
            'rejected_count' => (int) (clone $statsBaseQuery)->where('status', 'rejected')->count(),
            'rejected_amount' => (float) (clone $statsBaseQuery)->where('status', 'rejected')->sum('amount'),
            'status_counts' => [
                'all' => (int) (clone $statsBaseQuery)->count(),
                'pending' => (int) (clone $statsBaseQuery)->where('status', 'pending')->count(),
                'approved' => (int) (clone $statsBaseQuery)->where('status', 'approved')->count(),
                'rejected' => (int) (clone $statsBaseQuery)->where('status', 'rejected')->count(),
            ],
        ];

        return Inertia::render('Admin/Finance/Expenses/Index', [
            'expenses' => $query->paginate($perPage)->withQueryString(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'filters' => $request->only([
                'search',
                'status',
                'category_id',
                'user_id',
                'date_from',
                'date_to',
                'sort_by',
                'sort_dir',
                'per_page',
            ]),
            'stats' => $stats,
            'users' => $user->can('request_expenses_for_others')
                ? User::orderBy('name')->with('staff:id,user_id,staff_number')->get(['id', 'name', 'email'])
                : [],
            'canRequestForOthers' => $user->can('request_expenses_for_others'),
            'canApproveExpenses' => $user->can('approve_expenses'),
        ]);
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $query = $this->getFilteredQuery($request, $user);
        $expenses = $query->get();

        $headers = [
            'Date',
            'Title',
            'Category',
            'Amount (NGN)',
            'Status',
            'Beneficiary Name',
            'Staff Number',
            'Beneficiary Email',
            'Requested By',
            'Approved/Rejected By',
            'Description',
            'Rejection Reason',
        ];

        $callback = function () use ($headers, $expenses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->date ? $expense->date->format('Y-m-d') : '',
                    $expense->title,
                    $expense->category?->name ?? 'Uncategorized',
                    number_format((float) $expense->amount, 2, '.', ''),
                    ucfirst($expense->status),
                    $expense->user?->name ?? 'N/A',
                    $expense->user?->staff?->staff_number ?? 'N/A',
                    $expense->user?->email ?? 'N/A',
                    $expense->requester?->name ?? ($expense->user?->name ?? 'N/A'),
                    $expense->approver?->name ?? 'N/A',
                    $expense->description ?? '',
                    $expense->rejection_reason ?? '',
                ]);
            }

            fclose($file);
        };

        $fileName = 'expenses_report_' . now()->format('Y_m_d_His') . '.csv';

        return response()->stream($callback, 200, [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    protected function getFilteredQuery(Request $request, $user)
    {
        $query = Expense::with(['category', 'user.staff', 'requester.staff', 'approver']);

        // Scope view: regular users only see their own or requested by them
        if (!$user->can('request_expenses_for_others') && !$user->can('approve_expenses')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('requested_by', $user->id);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%")
                         ->orWhereHas('staff', fn($sq) => $sq->where('staff_number', 'like', "%{$term}%"));
                  })
                  ->orWhereHas('requester', function ($rq) use ($term) {
                      $rq->where('name', 'like', "%{$term}%");
                  });
            });
        }

        // Status filter (ignore 'all' or empty)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Category filter (ignore 'all' or empty)
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('expense_category_id', $request->category_id);
        }

        // Beneficiary user filter (ignore 'all' or empty)
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = in_array($request->sort_by, ['date', 'amount', 'title']) ? $request->sort_by : 'date';
        $sortDir = strtolower($request->sort_dir ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = Auth::id();
        if (Auth::user()->can('request_expenses_for_others') && !empty($validated['user_id'])) {
            $userId = $validated['user_id'];
        }

        Expense::create([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'expense_category_id' => $validated['expense_category_id'],
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
            'user_id' => $userId,
            'requested_by' => Auth::id(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Expense request submitted successfully.');
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Cannot edit processed expenses.');
        }

        $user = Auth::user();
        if ($expense->requested_by !== $user->id && $expense->user_id !== $user->id && !$user->can('request_expenses_for_others')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $expense->user_id;
        if ($user->can('request_expenses_for_others') && !empty($validated['user_id'])) {
            $userId = $validated['user_id'];
        }

        $expense->update([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'expense_category_id' => $validated['expense_category_id'],
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
            'user_id' => $userId,
        ]);

        return back()->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Cannot delete processed expenses.');
        }

        $user = Auth::user();
        if ($expense->requested_by !== $user->id && $expense->user_id !== $user->id && !$user->can('request_expenses_for_others')) {
            abort(403, 'Unauthorized action.');
        }

        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }

    public function approve(Expense $expense)
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Expense approved.');
    }

    public function reject(Request $request, Expense $expense)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        $expense->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Expense rejected.');
    }
}
