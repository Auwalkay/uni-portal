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

    public function downloadRoomImportTemplate()
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function headings(): array
            {
                return [
                    'hostel_name',
                    'block_name',
                    'floor_name',
                    'room_number',
                    'capacity',
                    'is_visible',
                ];
            }

            public function array(): array
            {
                return [
                    ['Mandela Hall', 'Block A', 'Ground Floor', '101', 4, 1],
                    ['Mandela Hall', 'Block A', 'Ground Floor', '102', 4, 1],
                    ['Mandela Hall', 'Block A', 'First Floor', '201', 2, 1],
                    ['Mandela Hall', 'Block B', 'Ground Floor', '103', 4, 1],
                ];
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'hostel_rooms_import_template.xlsx');
    }

    public function importRooms(Request $request, ?Hostel $hostel = null)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,txt,xls|max:10240',
            'hostel_id' => 'nullable|string',
            'block_id' => 'nullable|string',
            'floor_id' => 'nullable|string',
        ]);

        $targetHostel = $hostel;
        if (!$targetHostel && $request->filled('hostel_id')) {
            $targetHostel = Hostel::find($request->hostel_id);
        }

        $targetBlock = $request->filled('block_id') ? \App\Models\HostelBlock::find($request->block_id) : null;
        $targetFloor = $request->filled('floor_id') ? \App\Models\HostelFloor::find($request->floor_id) : null;

        $import = new \App\Imports\HostelRoomImport($targetHostel, $targetBlock, $targetFloor);
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        $hostelName = $targetHostel ? "'{$targetHostel->name}'" : "hostels";
        activity()
            ->causedBy(auth()->user())
            ->log("Imported {$import->importedCount} rooms for {$hostelName}");

        $msg = "Successfully processed Excel file: {$import->importedCount} room(s) processed ({$import->createdCount} created, {$import->updatedCount} updated).";
        if (count($import->errors) > 0) {
            $msg .= " Note: " . implode(" ", array_slice($import->errors, 0, 3));
        }

        return back()->with('success', $msg);
    }
}
