<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelFloor;

class HostelFloorController extends Controller
{
    public function store(Request $request, Hostel $hostel, HostelBlock $block)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $block->floors()->create($validated);

        return back()->with('success', 'Floor added successfully.');
    }

    public function destroy(Hostel $hostel, HostelBlock $block, HostelFloor $floor)
    {
        $activeBookings = \App\Models\HostelBooking::whereIn('status', ['pending', 'confirmed'])
            ->whereHas('room', function($q) use ($floor) {
                $q->where('hostel_floor_id', $floor->id);
            })->exists();

        if ($activeBookings) {
            return back()->with('error', 'Cannot delete floor. There are active bookings in rooms on this floor.');
        }

        $floor->delete();

        return back()->with('success', 'Floor removed successfully.');
    }
}
