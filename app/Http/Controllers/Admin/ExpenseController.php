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
        $query = Expense::with(['category', 'user.staff', 'requester.staff', 'approver'])->latest('date');

        // Filter based on roles/permissions
        $user = Auth::user();
        if (!$user->can('request_expenses_for_others') && !$user->can('approve_expenses')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('requested_by', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        return Inertia::render('Admin/Finance/Expenses/Index', [
            'expenses' => $query->paginate(10)->withQueryString(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'filters' => $request->only(['status', 'category_id']),
            'users' => $user->can('request_expenses_for_others')
                ? User::orderBy('name')->with('staff:id,user_id,staff_number')->get(['id', 'name', 'email'])
                : [],
            'canRequestForOthers' => $user->can('request_expenses_for_others'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
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
            'amount' => 'required|numeric|min:0',
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
