<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\HostelFee;

class HostelFeeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'hostel_id' => 'nullable|exists:hostels,id',
            'amount' => 'required|numeric|min:0',
        ]);

        // Check if fee configuration already exists for this session and hostel combo
        $existing = HostelFee::where('session_id', $validated['session_id'])
            ->where('hostel_id', $validated['hostel_id'] ?? null)
            ->first();

        if ($existing) {
            $existing->update($validated);
            return back()->with('success', 'Hostel fee updated successfully.');
        }

        HostelFee::create($validated);

        return back()->with('success', 'Hostel fee configured successfully.');
    }

    public function destroy(HostelFee $fee)
    {
        $fee->delete();

        return back()->with('success', 'Hostel fee configuration removed.');
    }
}
