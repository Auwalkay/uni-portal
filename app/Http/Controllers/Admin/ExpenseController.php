<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'user', 'approver'])->latest('date');

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
        ]);

        Expense::create([
            ...$validated,
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Expense request submitted successfully.');
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Cannot edit processed expenses.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'Cannot delete processed expenses.');
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
            'approved_by' => Auth::id(), // Rejected by
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Expense rejected.');
    }
}
