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
        $room->delete();

        return back()->with('success', 'Room removed successfully.');
    }
}
