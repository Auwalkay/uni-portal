<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\HostelFee;

class HostelFeeController extends Controller
{
    public function store(Request $request)
    {
        if ($request->has('hostel_id') && ($request->input('hostel_id') === '' || $request->input('hostel_id') === 'null' || $request->input('hostel_id') === null)) {
            $request->merge(['hostel_id' => null]);
        }

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

            activity('hostel_fee')
                ->performedOn($existing)
                ->causedBy(auth()->user())
                ->withProperties([
                    'amount' => $existing->amount,
                    'session_id' => $existing->session_id,
                    'hostel_id' => $existing->hostel_id,
                ])
                ->log("Hostel fee config for " . ($existing->hostel?->name ?? 'All Hostels') . " updated to ₦" . number_format($existing->amount, 2));

            return back()->with('success', 'Hostel fee updated successfully.');
        }

        $fee = HostelFee::create($validated);

        activity('hostel_fee')
            ->performedOn($fee)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $fee->amount,
                'session_id' => $fee->session_id,
                'hostel_id' => $fee->hostel_id,
            ])
            ->log("Hostel fee config for " . ($fee->hostel?->name ?? 'All Hostels') . " configured to ₦" . number_format($fee->amount, 2));

        return back()->with('success', 'Hostel fee configured successfully.');
    }

    public function update(Request $request, HostelFee $fee)
    {
        if ($request->has('hostel_id') && ($request->input('hostel_id') === '' || $request->input('hostel_id') === 'null' || $request->input('hostel_id') === null)) {
            $request->merge(['hostel_id' => null]);
        }

        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'hostel_id' => 'nullable|exists:hostels,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $fee->update($validated);

        activity('hostel_fee')
            ->performedOn($fee)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $fee->amount,
                'session_id' => $fee->session_id,
                'hostel_id' => $fee->hostel_id,
            ])
            ->log("Hostel fee config for " . ($fee->hostel?->name ?? 'All Hostels') . " updated to ₦" . number_format($fee->amount, 2));

        return back()->with('success', 'Hostel fee updated successfully.');
    }

    public function destroy(HostelFee $fee)
    {
        $feeName = $fee->hostel?->name ?? 'All Hostels';
        $feeAmount = $fee->amount;
        $fee->delete();

        activity('hostel_fee')
            ->causedBy(auth()->user())
            ->log("Hostel fee config for {$feeName} (₦" . number_format($feeAmount, 2) . ") removed");

        return back()->with('success', 'Hostel fee configuration removed.');
    }
}
