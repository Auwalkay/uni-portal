<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryAssignment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InventoryItemImport; // We will create this
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_inventory');

        $query = InventoryItem::with([
            'category',
            'requisitionItems' => function ($q) {
                $q->whereHas('requisition', function ($reqQ) {
                    $reqQ->whereIn('status', ['issued', 'approved']);
                })->with(['requisition.department', 'requisition.user']);
            }
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        }

        if ($request->filled('category_id')) {
            $query->where('inventory_category_id', $request->category_id);
        }

        $items = $query->latest()->paginate(15)->withQueryString();
        $categories = Cache::remember('inventory_categories', 3600, function () {
            return InventoryCategory::withCount('items')->get();
        });

        $stats = Cache::remember('inventory_stats', 60, function () {
            return [
                'total_items' => InventoryItem::sum('total_quantity'),
                'available_items' => InventoryItem::sum('available_quantity'),
                'assigned_items' => InventoryAssignment::where('status', 'assigned')->count(),
                'categories_count' => InventoryCategory::count(),
                'low_stock_count' => InventoryItem::whereRaw('available_quantity <= min_stock_level')->count(),
                'total_valuation' => InventoryItem::selectRaw('SUM(available_quantity * COALESCE(unit_cost, 0)) as val')->value('val') ?? 0,
                'pending_requisitions' => \App\Models\InventoryRequisition::where('status', 'pending')->count(),
                'pending_complaints' => \App\Models\InventoryComplaint::where('status', 'pending')->count(),
            ];
        });

        $recentLogs = \App\Models\InventoryStockLog::with(['item', 'user'])->latest()->limit(10)->get();
        $recentRequisitions = \App\Models\InventoryRequisition::with(['user', 'department', 'items.item'])->latest()->limit(10)->get();
        $recentAssignments = \App\Models\InventoryAssignment::with(['item', 'assignable.user', 'assignable.department'])->latest()->get();

        return Inertia::render('Admin/Inventory/Index', [
            'items' => $items,
            'categories' => $categories,
            'complaints_count' => \App\Models\InventoryComplaint::count(),
            'stats' => $stats,
            'recent_logs' => $recentLogs,
            'recent_requisitions' => $recentRequisitions,
            'recent_assignments' => $recentAssignments,
            'filters' => $request->only(['search', 'category_id']),
            'permissions' => [
                'can_manage' => $request->user()->can('manage_inventory'),
                'can_create' => $request->user()->can('create_inventory_items'),
                'can_edit' => $request->user()->can('edit_inventory_items'),
                'can_delete' => $request->user()->can('delete_inventory_items'),
                'can_restock' => $request->user()->can('restock_inventory_items'),
                'can_create_requisition' => $request->user()->can('create_inventory_requisitions'),
                'can_approve_requisition' => $request->user()->can('approve_inventory_requisitions'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create_inventory_items');

        $validated = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'total_quantity' => 'required|integer|min:0',
            'condition' => 'required|in:new,good,fair,poor',
            'unit_of_measure' => 'required|string|max:50',
            'min_stock_level' => 'required|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'item_type' => 'required|in:consumable,reusable',
            'supplier_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $validated['available_quantity'] = $validated['total_quantity'];

        $item = InventoryItem::create($validated);

        // Log initial stock creation
        if ($item->total_quantity > 0) {
            \App\Models\InventoryStockLog::create([
                'inventory_item_id' => $item->id,
                'type' => 'restock',
                'quantity' => $item->total_quantity,
                'user_id' => auth()->id(),
                'notes' => 'Initial stock creation',
            ]);
        }

        Cache::forget('inventory_stats');
        Cache::forget('inventory_categories');

        return redirect()->back()->with('success', 'Inventory item created successfully.');
    }

    public function update(Request $request, InventoryItem $item)
    {
        Gate::authorize('edit_inventory_items');

        $validated = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'total_quantity' => 'required|integer|min:0',
            'condition' => 'required|in:new,good,fair,poor',
            'unit_of_measure' => 'required|string|max:50',
            'min_stock_level' => 'required|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'item_type' => 'required|in:consumable,reusable',
            'supplier_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        // Adjust available quantity if total quantity changed
        $difference = $validated['total_quantity'] - $item->total_quantity;
        $validated['available_quantity'] = $item->available_quantity + $difference;

        if ($validated['available_quantity'] < 0) {
            return redirect()->back()->with('error', 'Cannot reduce total quantity below what is currently assigned.');
        }

        $item->update($validated);

        if ($difference !== 0) {
            \App\Models\InventoryStockLog::create([
                'inventory_item_id' => $item->id,
                'type' => $difference > 0 ? 'restock' : 'adjustment',
                'quantity' => abs($difference),
                'user_id' => auth()->id(),
                'notes' => $difference > 0 ? 'Manual stock increase' : 'Manual stock reduction adjustment',
            ]);
        }

        Cache::forget('inventory_stats');
        Cache::forget('inventory_categories');

        return redirect()->back()->with('success', 'Inventory item updated successfully.');
    }

    public function restock(Request $request, InventoryItem $item)
    {
        Gate::authorize('restock_inventory_items');

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        $item->increment('total_quantity', $validated['quantity']);
        $item->increment('available_quantity', $validated['quantity']);

        if (isset($validated['unit_cost'])) {
            $item->update(['unit_cost' => $validated['unit_cost']]);
        }
        if (isset($validated['supplier_name'])) {
            $item->update(['supplier_name' => $validated['supplier_name']]);
        }

        \App\Models\InventoryStockLog::create([
            'inventory_item_id' => $item->id,
            'type' => 'restock',
            'quantity' => $validated['quantity'],
            'user_id' => auth()->id(),
            'notes' => $validated['notes'] ?? 'Stock replenishment',
        ]);

        Cache::forget('inventory_stats');

        return redirect()->back()->with('success', "Added {$validated['quantity']} {$item->unit_of_measure} to {$item->name}.");
    }

    public function destroy(InventoryItem $item)
    {
        Gate::authorize('delete_inventory_items');

        // Check if item has active assignments
        if ($item->assignments()->where('status', 'assigned')->exists()) {
            return redirect()->back()->with('error', 'Cannot delete item with active assignments.');
        }

        $item->delete(); // Soft deletion
        Cache::forget('inventory_stats');
        Cache::forget('inventory_categories');

        return redirect()->back()->with('success', 'Inventory item soft deleted successfully.');
    }

    public function import(Request $request)
    {
        Gate::authorize('manage_inventory');

        $request->validate([
            'file' => 'required|file|extensions:csv,xls,xlsx|max:10240',
        ]);

        try {
            Excel::import(new InventoryItemImport, $request->file('file'));
            return redirect()->back()->with('success', 'Inventory items imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error during import: ' . $e->getMessage());
        }
    }

    public function storeCategory(Request $request)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_categories,name',
            'description' => 'nullable|string',
        ]);

        InventoryCategory::create($validated);
        Cache::forget('inventory_categories');

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function export()
    {
        Gate::authorize('view_inventory');
        return Excel::download(new \App\Exports\InventoryItemExport, 'inventory_items.xlsx');
    }

    public function exportAssignments()
    {
        Gate::authorize('view_inventory');
        return Excel::download(new \App\Exports\InventoryAssignmentExport, 'inventory_assignments.xlsx');
    }

    public function categoriesIndex(Request $request)
    {
        Gate::authorize('view_inventory');

        $categories = InventoryCategory::withCount('items')->get();

        return Inertia::render('Admin/Inventory/Categories', [
            'categories' => $categories,
            'permissions' => [
                'can_manage' => $request->user()->can('manage_inventory') || $request->user()->can('manage_inventory_categories'),
                'can_create' => $request->user()->can('create_inventory_items') || $request->user()->can('manage_inventory_categories'),
            ],
        ]);
    }

    public function auditLogsIndex(Request $request)
    {
        Gate::authorize('view_inventory');

        $query = \App\Models\InventoryStockLog::with(['item', 'user']);

        if ($request->filled('type') && $request->type !== 'ALL') {
            $query->where('type', $request->type);
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Inventory/AuditLogs', [
            'logs' => $logs,
            'filters' => $request->only(['type']),
            'permissions' => [
                'can_manage' => $request->user()->can('manage_inventory') || $request->user()->can('view_inventory_audit_logs'),
            ],
        ]);
    }
}
