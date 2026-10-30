<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\InventoryStockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryAssignmentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_inventory');

        $query = InventoryAssignment::with(['item', 'building', 'assignable']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($itemQ) use ($search) {
                    $itemQ->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                })
                ->orWhereHas('building', function ($bldgQ) use ($search) {
                    $bldgQ->where('name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhere('location_details', 'like', "%{$search}%");
            });
        }

        if ($request->filled('building_id') && $request->building_id !== 'ALL') {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        $assignments = $query->latest('assigned_at')->paginate(15)->withQueryString();

        $items = InventoryItem::where('available_quantity', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'available_quantity', 'total_quantity', 'unit_of_measure', 'condition']);

        $buildings = Building::orderBy('name')
            ->get(['id', 'name', 'code', 'building_type']);

        $stats = [
            'total_deployments' => InventoryAssignment::count(),
            'active_deployed_units' => (int) InventoryAssignment::where('status', 'assigned')->sum('quantity'),
            'returned_units' => (int) InventoryAssignment::where('status', 'returned')->sum('quantity'),
            'damaged_lost_units' => (int) InventoryAssignment::whereIn('status', ['damaged', 'lost'])->sum('quantity'),
        ];

        return Inertia::render('Admin/Inventory/Assignments', [
            'assignments' => $assignments,
            'items' => $items,
            'buildings' => $buildings,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'building_id']),
            'permissions' => [
                'can_manage' => $request->user()->can('manage_inventory') || $request->user()->can('manage_inventory_assignments') || $request->user()->can('assign_inventory'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'building_id' => 'required|exists:buildings,id',
            'quantity' => 'required|integer|min:1',
            'location_details' => 'nullable|string|max:255',
            'expected_return_date' => 'nullable|date|after_or_equal:today',
            'condition_on_assignment' => 'nullable|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);
        $building = Building::findOrFail($validated['building_id']);

        if ($item->available_quantity < $validated['quantity']) {
            return redirect()->back()->with('error', "Insufficient stock. Only {$item->available_quantity} {$item->unit_of_measure} available.");
        }

        DB::transaction(function () use ($validated, $item, $building) {
            InventoryAssignment::create([
                'inventory_item_id' => $validated['inventory_item_id'],
                'building_id' => $building->id,
                'quantity' => $validated['quantity'],
                'location_details' => $validated['location_details'] ?? null,
                'assignable_type' => Building::class,
                'assignable_id' => $building->id,
                'assigned_at' => now(),
                'expected_return_date' => $validated['expected_return_date'] ?? null,
                'status' => 'assigned',
                'condition_on_assignment' => $validated['condition_on_assignment'] ?? $item->condition,
            ]);

            $item->decrement('available_quantity', $validated['quantity']);

            InventoryStockLog::create([
                'inventory_item_id' => $item->id,
                'type' => 'issue',
                'quantity' => $validated['quantity'],
                'user_id' => auth()->id(),
                'notes' => "Deployed {$validated['quantity']} {$item->unit_of_measure} to {$building->name}" . ($validated['location_details'] ? " ({$validated['location_details']})" : ''),
            ]);
        });

        return redirect()->back()->with('success', "{$validated['quantity']} item(s) successfully deployed to {$building->name}.");
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
                'condition_on_return' => $validated['condition_on_return'] ?? 'good',
            ]);

            $qty = $assignment->quantity ?? 1;

            if ($validated['status'] === 'returned') {
                $assignment->item->increment('available_quantity', $qty);

                InventoryStockLog::create([
                    'inventory_item_id' => $assignment->inventory_item_id,
                    'type' => 'return',
                    'quantity' => $qty,
                    'user_id' => auth()->id(),
                    'notes' => "Returned {$qty} unit(s) from " . ($assignment->building?->name ?? 'Building') . ($assignment->location_details ? " ({$assignment->location_details})" : '') . ". Condition: " . ($validated['condition_on_return'] ?? 'good'),
                ]);
            } else {
                InventoryStockLog::create([
                    'inventory_item_id' => $assignment->inventory_item_id,
                    'type' => 'damage',
                    'quantity' => $qty,
                    'user_id' => auth()->id(),
                    'notes' => "Item marked as {$validated['status']} from " . ($assignment->building?->name ?? 'Building') . " ({$qty} unit(s)). Condition: " . ($validated['condition_on_return'] ?? 'N/A'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Item return processed successfully.');
    }
}
