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

        $query = InventoryItem::with('category');

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

        $stats = Cache::remember('inventory_stats', 300, function () {
            return [
                'total_items' => InventoryItem::sum('total_quantity'),
                'assigned_items' => InventoryAssignment::where('status', 'assigned')->count(),
                'categories_count' => InventoryCategory::count(),
                'pending_complaints' => \App\Models\InventoryComplaint::where('status', 'pending')->count(),
            ];
        });

        return Inertia::render('Admin/Inventory/Index', [
            'items' => $items,
            'categories' => $categories,
            'complaints_count' => \App\Models\InventoryComplaint::count(),
            'stats' => $stats,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'total_quantity' => 'required|integer|min:0',
            'condition' => 'required|in:new,good,fair,poor',
        ]);

        $validated['available_quantity'] = $validated['total_quantity'];

        InventoryItem::create($validated);

        return redirect()->back()->with('success', 'Inventory item created successfully.');
    }

    public function update(Request $request, InventoryItem $item)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'inventory_category_id' => 'required|exists:inventory_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'total_quantity' => 'required|integer|min:0',
            'condition' => 'required|in:new,good,fair,poor',
        ]);

        // Adjust available quantity if total quantity changed
        $difference = $validated['total_quantity'] - $item->total_quantity;
        $validated['available_quantity'] = $item->available_quantity + $difference;

        if ($validated['available_quantity'] < 0) {
            return redirect()->back()->with('error', 'Cannot reduce total quantity below what is currently assigned.');
        }

        $item->update($validated);

        return redirect()->back()->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $item)
    {
        Gate::authorize('manage_inventory');

        // Check if item has active assignments
        if ($item->assignments()->where('status', 'assigned')->exists()) {
            return redirect()->back()->with('error', 'Cannot delete item with active assignments.');
        }

        $item->delete();

        return redirect()->back()->with('success', 'Inventory item deleted successfully.');
    }

    public function import(Request $request)
    {
        Gate::authorize('manage_inventory');

        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx|max:10240',
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
}
