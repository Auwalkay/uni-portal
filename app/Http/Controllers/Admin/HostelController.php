<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HostelRequest;
use App\Models\Hostel;
use App\Models\Session;
use App\Services\HostelService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HostelController extends Controller
{
    public function __construct(
        protected HostelService $hostelService
    ) {}

    public function index()
    {
        $user = auth()->user();

        if (!$user->can('manage_hostels') &&
            !$user->can('manage_hostel_fees') &&
            !$user->can('view_hostel_bookings') &&
            !$user->can('view_male_hostel_bookings') &&
            !$user->can('view_female_hostel_bookings') &&
            !$user->hasRole('admin')) {
            abort(403, 'Unauthorized access to hostel records.');
        }

        $currentSession = Session::current();
        $hostels = $this->hostelService->getHostelsForUser($user, $currentSession?->id);
        $sessions = Session::latest()->get();

        return Inertia::render('Admin/Hostels/Index', [
            'hostels' => $hostels,
            'sessions' => $sessions,
            'currentSession' => $currentSession,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Hostels/Create');
    }

    public function store(HostelRequest $request)
    {
        $hostel = Hostel::create($request->sanitized());

        activity('hostel')
            ->performedOn($hostel)
            ->causedBy(auth()->user())
            ->log("Hostel '{$hostel->name}' created");

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel created successfully.');
    }

    public function show(Hostel $hostel)
    {
        $currentSession = Session::current();
        $loadedHostel = $this->hostelService->getHostelWithDetails($hostel, $currentSession?->id);

        return Inertia::render('Admin/Hostels/Show', [
            'hostel' => $loadedHostel,
            'currentSession' => $currentSession,
        ]);
    }

    public function edit(Hostel $hostel)
    {
        return Inertia::render('Admin/Hostels/Edit', [
            'hostel' => $hostel,
        ]);
    }

    public function update(HostelRequest $request, Hostel $hostel)
    {
        $hostel->update($request->sanitized());

        activity('hostel')
            ->performedOn($hostel)
            ->causedBy(auth()->user())
            ->log("Hostel '{$hostel->name}' details updated");

        return redirect()->route('admin.hostels.index')->with('success', 'Hostel updated successfully.');
    }

    public function destroy(Hostel $hostel)
    {
        if ($this->hostelService->hasActiveBookings($hostel)) {
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
        $this->hostelService->toggleVisibility($hostel, auth()->user());

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

        $stats = $this->hostelService->importRooms(
            $request->file('file'),
            $targetHostel,
            $request->input('block_id'),
            $request->input('floor_id'),
            auth()->user()
        );

        $msg = "Successfully processed Excel file: {$stats['imported']} room(s) processed ({$stats['created']} created, {$stats['updated']} updated).";
        if (count($stats['errors']) > 0) {
            $msg .= " Note: " . implode(" ", array_slice($stats['errors'], 0, 3));
        }

        return back()->with('success', $msg);
    }
}
