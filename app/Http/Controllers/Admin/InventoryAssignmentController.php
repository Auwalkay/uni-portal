<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\InventoryStockLog;
use App\Models\Staff;
use App\Mail\ItemAssignedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InventoryAssignmentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_inventory');

        $query = InventoryAssignment::with(['item', 'building', 'staff.user', 'staff.department', 'assignable.user']);

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
                ->orWhereHas('staff', function ($staffQ) use ($search) {
                    $staffQ->where('staff_number', 'like', "%{$search}%")
                           ->orWhereHas('user', function ($userQ) use ($search) {
                               $userQ->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                           });
                })
                ->orWhere('location_details', 'like', "%{$search}%");
            });
        }

        if ($request->filled('target_type') && $request->target_type !== 'ALL') {
            if ($request->target_type === 'building') {
                $query->where(function ($q) {
                    $q->whereNotNull('building_id')
                      ->orWhere('assignable_type', Building::class);
                });
            } elseif ($request->target_type === 'staff') {
                $query->where(function ($q) {
                    $q->whereNotNull('staff_id')
                      ->orWhere('assignable_type', Staff::class);
                });
            }
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
            'building_units' => (int) InventoryAssignment::where('status', 'assigned')->where(function ($q) {
                $q->whereNotNull('building_id')->orWhere('assignable_type', Building::class);
            })->sum('quantity'),
            'staff_custody_units' => (int) InventoryAssignment::where('status', 'assigned')->where(function ($q) {
                $q->whereNotNull('staff_id')->orWhere('assignable_type', Staff::class);
            })->sum('quantity'),
            'returned_units' => (int) InventoryAssignment::where('status', 'returned')->sum('quantity'),
            'damaged_lost_units' => (int) InventoryAssignment::whereIn('status', ['damaged', 'lost'])->sum('quantity'),
        ];

        return Inertia::render('Admin/Inventory/Assignments', [
            'assignments' => $assignments,
            'items' => $items,
            'buildings' => $buildings,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'building_id', 'target_type']),
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
            'assignment_target' => 'required|in:building,staff',
            'building_id' => 'required_if:assignment_target,building|nullable|exists:buildings,id',
            'staff_id' => 'required_if:assignment_target,staff|nullable|exists:staff,id',
            'quantity' => 'required|integer|min:1',
            'location_details' => 'nullable|string|max:255',
            'expected_return_date' => 'nullable|date|after_or_equal:today',
            'condition_on_assignment' => 'nullable|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        if ($item->available_quantity < $validated['quantity']) {
            return redirect()->back()->with('error', "Insufficient stock. Only {$item->available_quantity} {$item->unit_of_measure} available.");
        }

        $targetLabel = '';

        DB::transaction(function () use ($validated, $item, &$targetLabel) {
            if ($validated['assignment_target'] === 'building') {
                $building = Building::findOrFail($validated['building_id']);
                $targetLabel = $building->name;

                InventoryAssignment::create([
                    'inventory_item_id' => $validated['inventory_item_id'],
                    'building_id' => $building->id,
                    'staff_id' => null,
                    'quantity' => $validated['quantity'],
                    'location_details' => $validated['location_details'] ?? null,
                    'assignable_type' => Building::class,
                    'assignable_id' => $building->id,
                    'assigned_at' => now(),
                    'expected_return_date' => $validated['expected_return_date'] ?? null,
                    'status' => 'assigned',
                    'condition_on_assignment' => $validated['condition_on_assignment'] ?? $item->condition,
                ]);

                InventoryStockLog::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'issue',
                    'quantity' => $validated['quantity'],
                    'user_id' => auth()->id(),
                    'notes' => "Deployed {$validated['quantity']} {$item->unit_of_measure} to {$building->name}" . ($validated['location_details'] ? " ({$validated['location_details']})" : ''),
                ]);
            } else {
                $staff = Staff::with('user')->findOrFail($validated['staff_id']);
                $targetLabel = $staff->user?->name ?? 'Staff Member';

                $assignment = InventoryAssignment::create([
                    'inventory_item_id' => $validated['inventory_item_id'],
                    'building_id' => null,
                    'staff_id' => $staff->id,
                    'quantity' => $validated['quantity'],
                    'location_details' => $validated['location_details'] ?? null,
                    'assignable_type' => Staff::class,
                    'assignable_id' => $staff->id,
                    'assigned_at' => now(),
                    'expected_return_date' => $validated['expected_return_date'] ?? null,
                    'status' => 'assigned',
                    'condition_on_assignment' => $validated['condition_on_assignment'] ?? $item->condition,
                ]);

                InventoryStockLog::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'issue',
                    'quantity' => $validated['quantity'],
                    'user_id' => auth()->id(),
                    'notes' => "Assigned {$validated['quantity']} {$item->unit_of_measure} to {$targetLabel}" . ($staff->staff_number ? " [{$staff->staff_number}]" : '') . ($validated['location_details'] ? " ({$validated['location_details']})" : ''),
                ]);

                // Send email notification to staff member
                try {
                    if ($staff->user?->email) {
                        Mail::to($staff->user->email)->send(
                            new ItemAssignedMail($assignment->load(['item', 'staff.user']))
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send ItemAssignedMail: ' . $e->getMessage());
                }
            }

            $item->decrement('available_quantity', $validated['quantity']);
        });

        return redirect()->back()->with('success', "{$validated['quantity']} item(s) successfully assigned to {$targetLabel}.");
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

            $destination = 'General Store';
            if ($assignment->building) {
                $destination = $assignment->building->name . ($assignment->location_details ? " ({$assignment->location_details})" : '');
            } elseif ($assignment->staff) {
                $destination = ($assignment->staff->user?->name ?? 'Staff') . ($assignment->staff->staff_number ? " [{$assignment->staff->staff_number}]" : '');
            } elseif ($assignment->assignable?->user) {
                $destination = $assignment->assignable->user->name;
            }

            if ($validated['status'] === 'returned') {
                $assignment->item->increment('available_quantity', $qty);

                InventoryStockLog::create([
                    'inventory_item_id' => $assignment->inventory_item_id,
                    'type' => 'return',
                    'quantity' => $qty,
                    'user_id' => auth()->id(),
                    'notes' => "Returned {$qty} unit(s) from {$destination}. Condition: " . ($validated['condition_on_return'] ?? 'good'),
                ]);
            } else {
                InventoryStockLog::create([
                    'inventory_item_id' => $assignment->inventory_item_id,
                    'type' => 'damage',
                    'quantity' => $qty,
                    'user_id' => auth()->id(),
                    'notes' => "Item marked as {$validated['status']} from {$destination} ({$qty} unit(s)). Condition: " . ($validated['condition_on_return'] ?? 'N/A'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Item return processed successfully.');
    }

    public function searchStaff(Request $request)
    {
        Gate::authorize('view_inventory');

        $query = trim($request->get('query', ''));

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $staff = Staff::with(['user:id,name,email', 'department:id,name'])
            ->where(function ($q) use ($query) {
                $q->where('staff_number', 'like', "%{$query}%")
                  ->orWhereHas('user', function ($uq) use ($query) {
                      $uq->where('name', 'like', "%{$query}%")
                         ->orWhere('email', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->user?->name ?? 'Unknown Staff',
                    'staff_number' => $s->staff_number,
                    'email' => $s->user?->email,
                    'department' => $s->department?->name ?? 'General Staff',
                    'label' => ($s->user?->name ?? 'Staff') . ($s->staff_number ? " ({$s->staff_number})" : '') . ($s->department ? " - {$s->department->name}" : ''),
                ];
            });

        return response()->json($staff);
    }
}
