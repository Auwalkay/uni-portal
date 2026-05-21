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
        $floor->delete();

        return back()->with('success', 'Floor removed successfully.');
    }
}
