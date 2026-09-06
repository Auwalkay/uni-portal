<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryRequisition;
use App\Models\InventoryRequisitionItem;
use App\Models\InventoryStockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryRequisitionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_inventory');

        $query = InventoryRequisition::with(['user', 'department', 'approvedBy', 'items.item']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('requisition_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        $requisitions = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Inventory/Requisitions', [
            'requisitions' => $requisitions,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create_inventory_requisitions');

        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.requested_quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            $requisitionNumber = 'SIV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $requisition = InventoryRequisition::create([
                'requisition_number' => $requisitionNumber,
                'user_id' => auth()->id(),
                'department_id' => $validated['department_id'] ?? auth()->user()?->department_id,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = InventoryItem::findOrFail($itemData['inventory_item_id']);
                InventoryRequisitionItem::create([
                    'inventory_requisition_id' => $requisition->id,
                    'inventory_item_id' => $item->id,
                    'requested_quantity' => $itemData['requested_quantity'],
                    'approved_quantity' => $itemData['requested_quantity'], // Default match
                    'unit_of_measure' => $item->unit_of_measure,
                ]);
            }
        });

        Cache::forget('inventory_stats');

        return redirect()->back()->with('success', 'Store Requisition submitted successfully.');
    }

    public function approve(Request $request, InventoryRequisition $requisition)
    {
        Gate::authorize('approve_inventory_requisitions');

        if ($requisition->status === 'issued' || $requisition->status === 'approved') {
            return redirect()->back()->with('info', 'Requisition has already been approved/issued.');
        }

        $requisition->load('items.item');

        // Check stock availability
        foreach ($requisition->items as $reqItem) {
            if ($reqItem->item->available_quantity < $reqItem->requested_quantity) {
                return redirect()->back()->with('error', "Insufficient stock for {$reqItem->item->name}. Available: {$reqItem->item->available_quantity} {$reqItem->item->unit_of_measure}.");
            }
        }

        DB::transaction(function () use ($requisition) {
            foreach ($requisition->items as $reqItem) {
                $item = $reqItem->item;
                $item->decrement('available_quantity', $reqItem->requested_quantity);
                $reqItem->update(['approved_quantity' => $reqItem->requested_quantity]);

                InventoryStockLog::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'issue',
                    'quantity' => $reqItem->requested_quantity,
                    'user_id' => auth()->id(),
                    'notes' => "Issued for Requisition #{$requisition->requisition_number}",
                ]);
            }

            $requisition->update([
                'status' => 'issued',
                'approved_by' => auth()->id(),
                'issued_at' => now(),
            ]);
        });

        Cache::forget('inventory_stats');

        return redirect()->back()->with('success', "Requisition #{$requisition->requisition_number} approved and stock issued.");
    }

    public function reject(Request $request, InventoryRequisition $requisition)
    {
        Gate::authorize('approve_inventory_requisitions');

        $requisition->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'notes' => $request->input('notes', 'Requisition rejected by store manager.'),
        ]);

        Cache::forget('inventory_stats');

        return redirect()->back()->with('success', "Requisition #{$requisition->requisition_number} marked as rejected.");
    }

    public function downloadVoucher(InventoryRequisition $requisition)
    {
        Gate::authorize('view_inventory');

        $requisition->load(['user', 'department', 'approvedBy', 'items.item']);

        $pdf = Pdf::loadView('documents.store_issue_voucher', [
            'requisition' => $requisition,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download("Store_Issue_Voucher_{$requisition->requisition_number}.pdf");
    }
}
