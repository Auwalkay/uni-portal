<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class InventoryAssignmentController extends Controller
{
    public function store(Request $request)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'staff_id' => 'required|exists:staff,id',
            'expected_return_date' => 'nullable|date|after:today',
            'condition_on_assignment' => 'nullable|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        if ($item->available_quantity <= 0) {
            return redirect()->back()->with('error', 'Item is not available for assignment.');
        }

        $assignment = null;
        DB::transaction(function () use ($validated, $item, &$assignment) {
            $assignment = InventoryAssignment::create([
                'inventory_item_id' => $validated['inventory_item_id'],
                'assignable_type' => Staff::class,
                'assignable_id' => $validated['staff_id'],
                'assigned_at' => now(),
                'expected_return_date' => $validated['expected_return_date'],
                'status' => 'assigned',
                'condition_on_assignment' => $validated['condition_on_assignment'] ?? $item->condition,
            ]);

            $item->decrement('available_quantity');
        });

        // Send Email
        $staff = Staff::with('user')->find($validated['staff_id']);
        if ($staff && $staff->user && $staff->user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($staff->user->email)->send(new \App\Mail\ItemAssignedMail($assignment->load('item')));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send item assigned email: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Item assigned successfully.');
    }

    public function searchStaff(Request $request)
    {
        Gate::authorize('view_inventory');

        $search = $request->query('query');

        if (empty($search)) {
            return response()->json([]);
        }

        // Assuming Staff has a relationship with User or has name fields directly
        // Let's check Staff model or assume it has a user relationship with name
        // Based on common patterns, staff usually links to users table for names
        $staff = Staff::with('user')
            ->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->user->name . ' (' . ($s->staff_id ?? 'N/A') . ')',
                ];
            });

        return response()->json($staff);
    }

    public function returnItem(Request $request, InventoryAssignment $assignment)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'condition_on_return' => 'nullable|string|max:255',
            'status' => 'required|in:returned,lost,damaged',
        ]);

        DB::transaction(function () use ($assignment, $validated) {
            $assignment->update([
                'returned_at' => now(),
                'status' => $validated['status'],
                'condition_on_return' => $validated['condition_on_return'],
            ]);

            if ($validated['status'] === 'returned') {
                $assignment->item->increment('available_quantity');
            }
            // If lost or damaged, we might not increment available_quantity, 
            // and we might need to decrement total_quantity if it's lost permanently.
            // For now, just handle returned status incrementing available.
        });

        return redirect()->back()->with('success', 'Item return processed successfully.');
    }
}
