<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hostel;
use App\Models\HostelBlock;

class HostelBlockController extends Controller
{
    public function store(Request $request, Hostel $hostel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $hostel->blocks()->create($validated);

        return back()->with('success', 'Block added successfully.');
    }

    public function destroy(Hostel $hostel, HostelBlock $block)
    {
        $activeBookings = \App\Models\HostelBooking::whereIn('status', ['pending', 'confirmed'])
            ->whereHas('room.floor', function($q) use ($block) {
                $q->where('hostel_block_id', $block->id);
            })->exists();

        if ($activeBookings) {
            return back()->with('error', 'Cannot delete block. There are active bookings in rooms on this block.');
        }

        // Delete block and conventionally all nested floors/rooms due to cascading migrations
        $block->delete();

        return back()->with('success', 'Block removed successfully.');
    }
}
