<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Hostel;
use App\Models\Session;
use Inertia\Inertia;

class HostelController extends Controller
{
    public function index()
    {
        $hostels = Hostel::withCount('floors', 'fees')->latest()->get();
        $sessions = Session::latest()->get();

        return Inertia::render('Admin/Hostels/Index', [
            'hostels' => $hostels,
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hostels,name',
            'gender_type' => 'required|in:male,female,mixed',
            'description' => 'nullable|string',
        ]);

        Hostel::create($validated);

        return back()->with('success', 'Hostel created successfully.');
    }

    public function show(Hostel $hostel)
    {
        $hostel->load(['blocks.floors.rooms']);

        return Inertia::render('Admin/Hostels/Show', [
            'hostel' => $hostel,
        ]);
    }

    public function update(Request $request, Hostel $hostel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hostels,name,' . $hostel->id,
            'gender_type' => 'required|in:male,female,mixed',
            'description' => 'nullable|string',
        ]);

        $hostel->update($validated);

        return back()->with('success', 'Hostel updated successfully.');
    }

    public function destroy(Hostel $hostel)
    {
        $hostel->delete();

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel deleted successfully.');
    }
}
