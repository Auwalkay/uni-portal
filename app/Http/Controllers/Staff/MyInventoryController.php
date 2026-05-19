<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\InventoryAssignment;
use App\Models\InventoryComplaint;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyInventoryController extends Controller
{
    public function index()
    {
        $staff = Staff::where('user_id', auth()->id())->firstOrFail();

        $assignments = InventoryAssignment::where('assignable_type', Staff::class)
            ->where('assignable_id', $staff->id)
            ->with('item')
            ->latest()
            ->get();

        $complaints = InventoryComplaint::where('user_id', auth()->id())
            ->with('item')
            ->latest()
            ->get();

        return Inertia::render('Staff/Inventory/Index', [
            'assignments' => $assignments,
            'complaints' => $complaints,
        ]);
    }

    public function storeComplaint(Request $request)
    {
        $staff = Staff::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        // Verify that the item is actually assigned to this staff
        $assignment = InventoryAssignment::where('assignable_type', Staff::class)
            ->where('assignable_id', $staff->id)
            ->where('inventory_item_id', $validated['inventory_item_id'])
            ->where('status', 'assigned')
            ->first();

        if (!$assignment) {
            return redirect()->back()->with('error', 'You can only report issues for items currently assigned to you.');
        }

        $complaint = InventoryComplaint::create([
            'user_id' => auth()->id(),
            'inventory_assignment_id' => $assignment->id,
            'inventory_item_id' => $validated['inventory_item_id'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        // Send Email to Admins
        try {
            $admins = \App\Models\User::permission('manage_inventory')->get();
            foreach ($admins as $admin) {
                if ($admin->email) {
                    \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\ComplaintReportedMail($complaint->load(['item', 'user'])));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send complaint email: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Complaint submitted successfully.');
    }
}
