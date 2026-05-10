<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DeviceAttendanceController extends Controller
{
    /**
     * Handle incoming logs from 3rd party biometric devices.
     * Expected JSON: { "staff_id": "STF-001", "timestamp": "2026-05-10 08:30:00", "type": "in/out" }
     */
    public function sync(Request $request)
    {
        // Simple token check for security (In real app, use Sanctum/Passport)
        if ($request->header('X-Device-Token') !== config('services.attendance_device.token')) {
            return response()->json(['message' => 'Unauthorized device'], 401);
        }

        $request->validate([
            'staff_id' => 'required',
            'timestamp' => 'required|date',
            'type' => 'required|in:in,out',
        ]);

        $staff = Staff::where('id', $request->staff_id)
            ->orWhere('employee_id', $request->staff_id)
            ->first();

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $dateTime = Carbon::parse($request->timestamp);
        $date = $dateTime->toDateString();
        $time = $dateTime->toTimeString();

        $attendance = Attendance::firstOrCreate(
            ['staff_id' => $staff->id, 'date' => $date],
            ['source' => 'device']
        );

        if ($request->type === 'in') {
            $attendance->update([
                'clock_in' => $time,
                'status' => $dateTime->hour >= 9 ? 'late' : 'present', // Configurable late threshold
            ]);
        } else {
            $attendance->update([
                'clock_out' => $time,
            ]);
        }

        return response()->json([
            'message' => 'Attendance recorded successfully',
            'staff' => $staff->user->name,
            'time' => $time
        ]);
    }
}
