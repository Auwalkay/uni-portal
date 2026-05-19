<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryComplaintController extends Controller
{
    public function index()
    {
        Gate::authorize('view_inventory');

        $complaints = InventoryComplaint::with(['user', 'item'])
            ->latest()
            ->paginate(15);

        return response()->json($complaints); // We will fetch this via API in the Vue component
    }

    public function update(Request $request, InventoryComplaint $complaint)
    {
        Gate::authorize('manage_inventory');

        $validated = $request->validate([
            'status' => 'required|in:pending,reviewing,resolved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $complaint->update($validated);

        // If resolved, we might want to update the item condition or do something else.
        // For now, just update the complaint status.

        return redirect()->back()->with('success', 'Complaint updated successfully.');
    }
}
