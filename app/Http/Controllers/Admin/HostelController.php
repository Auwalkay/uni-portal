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
        $currentSession = Session::current();
        $sessionId = $currentSession?->id;

        $hostels = Hostel::withCount(['floors', 'fees'])
            ->with(['blocks.floors.rooms' => function ($q) use ($sessionId) {
                $q->with(['bookings' => function ($bq) use ($sessionId) {
                    if ($sessionId) {
                        $bq->where('session_id', $sessionId);
                    }
                    $bq->whereIn('status', ['pending', 'confirmed']);
                }]);
            }])
            ->latest()
            ->get();

        $sessions = Session::latest()->get();

        return Inertia::render('Admin/Hostels/Index', [
            'hostels' => $hostels,
            'sessions' => $sessions,
            'currentSession' => $currentSession,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hostels,name',
            'gender_type' => 'required|in:male,female,mixed',
            'description' => 'nullable|string',
        ]);

        $hostel = Hostel::create($validated);

        activity('hostel')
            ->performedOn($hostel)
            ->causedBy(auth()->user())
            ->log("Hostel '{$hostel->name}' created");

        return back()->with('success', 'Hostel created successfully.');
    }

    public function show(Hostel $hostel)
    {
        $currentSession = Session::current();
        $sessionId = $currentSession?->id;

        $hostel->load([
            'blocks.floors.rooms' => function ($q) use ($sessionId) {
                $q->with(['bookings' => function ($bq) use ($sessionId) {
                    if ($sessionId) {
                        $bq->where('session_id', $sessionId);
                    }
                    $bq->whereIn('status', ['pending', 'confirmed'])
                        ->with(['student.user', 'student.department', 'invoice']);
                }]);
            }
        ]);

        return Inertia::render('Admin/Hostels/Show', [
            'hostel' => $hostel,
            'currentSession' => $currentSession,
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

        activity('hostel')
            ->performedOn($hostel)
            ->causedBy(auth()->user())
            ->log("Hostel '{$hostel->name}' details updated");

        return back()->with('success', 'Hostel updated successfully.');
    }

    public function destroy(Hostel $hostel)
    {
        $activeBookings = \App\Models\HostelBooking::whereIn('status', ['pending', 'confirmed'])
            ->whereHas('room.floor.block', function($q) use ($hostel) {
                $q->where('hostel_id', $hostel->id);
            })->exists();

        if ($activeBookings) {
            return back()->with('error', 'Cannot delete hostel. There are active bookings in this hostel.');
        }

        $hostelName = $hostel->name;
        $hostel->delete();

        activity('hostel')
            ->causedBy(auth()->user())
            ->log("Hostel '{$hostelName}' deleted");

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel deleted successfully.');
    }

    public function toggleVisibility(Hostel $hostel)
    {
        $hostel->update([
            'is_visible' => !$hostel->is_visible
        ]);

        $statusText = $hostel->is_visible ? 'unblocked (made visible)' : 'blocked (hidden)';
        activity('hostel')
            ->performedOn($hostel)
            ->causedBy(auth()->user())
            ->log("Hostel '{$hostel->name}' {$statusText}");

        return back()->with('success', 'Hostel visibility updated.');
    }
}
