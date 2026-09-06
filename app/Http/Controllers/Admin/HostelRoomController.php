<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelFloor;
use App\Models\HostelRoom;

class HostelRoomController extends Controller
{
    public function store(Request $request, Hostel $hostel, HostelBlock $block, HostelFloor $floor)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:20',
        ]);

        $floor->rooms()->create($validated);

        return back()->with('success', 'Room added successfully.');
    }

    public function update(Request $request, Hostel $hostel, HostelBlock $block, HostelFloor $floor, HostelRoom $room)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:20',
        ]);

        $room->update($validated);

        return back()->with('success', 'Room updated successfully.');
    }

    public function destroy(Hostel $hostel, HostelBlock $block, HostelFloor $floor, HostelRoom $room)
    {
        $activeBookings = $room->bookings()->whereIn('status', ['pending', 'confirmed'])->exists();
        if ($activeBookings) {
            return back()->with('error', 'Cannot delete room. There are active bookings in this room.');
        }

        $room->delete();

        return back()->with('success', 'Room removed successfully.');
    }

    public function toggleVisibility(Hostel $hostel, HostelBlock $block, HostelFloor $floor, HostelRoom $room)
    {
        $room->update([
            'is_visible' => !$room->is_visible
        ]);

        return back()->with('success', 'Room visibility updated.');
    }

    public function toggleSuspension(Hostel $hostel, HostelBlock $block, HostelFloor $floor, HostelRoom $room)
    {
        $room->update([
            'is_suspended' => !$room->is_suspended
        ]);

        return back()->with('success', $room->is_suspended ? 'Room suspended successfully.' : 'Room unsuspended successfully.');
    }
}
