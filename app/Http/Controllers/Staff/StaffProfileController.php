<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class StaffProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = auth()->user();
        $staff = $user->staff()
            ->with(['department.faculty', 'unit', 'state', 'lga'])
            ->firstOrFail();
            
        // Ensure user relation is set to the current user object
        $staff->setRelation('user', $user);

        // Attendance Data with Month & Year Filtering
        $selectedMonth = (int)$request->query('month', now()->month);
        $selectedYear = (int)$request->query('year', now()->year);

        $attendanceStats = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'on_leave' => 0,
            'total' => 0,
            'rate' => 0,
        ];

        $weeklyAttendance = [];

        $attendances = \App\Models\Attendance::where('staff_id', $staff->id)
            ->whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->orderBy('date', 'asc')
            ->get();

        $attendanceStats['present'] = $attendances->where('status', 'present')->count();
        $attendanceStats['late'] = $attendances->where('status', 'late')->count();
        $attendanceStats['absent'] = $attendances->where('status', 'absent')->count();
        $attendanceStats['on_leave'] = $attendances->where('status', 'on_leave')->count();
        $attendanceStats['total'] = $attendances->count();
        $attendanceStats['rate'] = $attendanceStats['total'] > 0 
            ? round((($attendanceStats['present'] + $attendanceStats['late']) / $attendanceStats['total']) * 100, 1)
            : 0;

        // Group attendances by week
        $grouped = $attendances->groupBy(function ($item) {
            $carbon = \Carbon\Carbon::parse($item->date);
            $startOfWeek = $carbon->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            $endOfWeek = $carbon->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
            return 'Week of ' . $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M, Y');
        });

        foreach ($grouped as $weekLabel => $items) {
            $weeklyAttendance[] = [
                'week' => $weekLabel,
                'start_date' => \Carbon\Carbon::parse($items->first()->date)->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'),
                'records' => $items->map(fn($item) => [
                    'id' => $item->id,
                    'date' => \Carbon\Carbon::parse($item->date)->format('Y-m-d'),
                    'day_name' => \Carbon\Carbon::parse($item->date)->format('l'),
                    'formatted_date' => \Carbon\Carbon::parse($item->date)->format('d M, Y'),
                    'clock_in' => $item->clock_in ? \Carbon\Carbon::parse($item->clock_in)->format('H:i') : null,
                    'clock_out' => $item->clock_out ? \Carbon\Carbon::parse($item->clock_out)->format('H:i') : null,
                    'status' => $item->status,
                    'notes' => $item->notes,
                ])->values(),
                'present_count' => $items->whereIn('status', ['present', 'late'])->count(),
                'total_count' => $items->count(),
            ];
        }

        return Inertia::render('Staff/Profile/Edit', [
            'staff' => $staff,
            'status' => session('status'),
            'attendanceData' => [
                'weekly' => $weeklyAttendance,
                'stats' => $attendanceStats,
                'filters' => [
                    'month' => $selectedMonth,
                    'year' => $selectedYear,
                ],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $staff = auth()->user()->staff()->firstOrFail();

        $validated = $request->validate([
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'specialization' => 'nullable|string|max:255',
            'research_interests' => 'nullable|string',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_address' => 'nullable|string',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            $user = auth()->user();
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        $staff->update($request->only([
            'phone_number', 'address', 'specialization', 'research_interests',
            'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_address', 'next_of_kin_relationship'
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function show()
    {
        $user = auth()->user();
        $staff = $user->staff()
            ->with(['department.faculty', 'unit', 'state', 'lga'])
            ->firstOrFail();

        $staff->setRelation('user', $user);

        return Inertia::render('Staff/Profile/Show', [
            'staff' => $staff,
        ]);
    }
}
