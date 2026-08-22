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
            'hostel_id' => 'nullable',
            'hostel_ids' => 'nullable|array',
            'amount' => 'required|numeric|min:0',
        ]);

        $hostelIds = $request->input('hostel_ids', []);
        
        // If single hostel_id was passed
        if (empty($hostelIds)) {
            $hostelId = $request->input('hostel_id');
            if ($hostelId === '' || $hostelId === 'all' || $hostelId === 'null') {
                $hostelId = null;
            }
            $hostelIds = [$hostelId];
        }

        foreach ($hostelIds as $hId) {
            $targetHostelId = ($hId === 'all' || $hId === 'null' || $hId === '') ? null : $hId;

            $fee = HostelFee::updateOrCreate(
                [
                    'session_id' => $validated['session_id'],
                    'hostel_id' => $targetHostelId,
                ],
                [
                    'amount' => $validated['amount'],
                ]
            );

            $hostelName = $fee->hostel?->name ?? 'All Hostels';

            activity('hostel_fee')
                ->performedOn($fee)
                ->causedBy(auth()->user())
                ->withProperties([
                    'amount' => $fee->amount,
                    'session_id' => $fee->session_id,
                    'hostel_id' => $fee->hostel_id,
                ])
                ->log("Hostel fee config for {$hostelName} set to ₦" . number_format($fee->amount, 2));
        }

        return back()->with('success', 'Hostel fee configuration saved successfully.');
    }

    public function update(Request $request, HostelFee $fee)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'hostel_id' => 'nullable',
            'hostel_ids' => 'nullable|array',
            'amount' => 'required|numeric|min:0',
        ]);

        $hostelIds = $request->input('hostel_ids', []);

        if (empty($hostelIds)) {
            $hostelId = $request->input('hostel_id');
            if ($hostelId === '' || $hostelId === 'all' || $hostelId === 'null') {
                $hostelId = null;
            }
            $hostelIds = [$hostelId];
        }

        // Update the current fee record with the first hostel ID
        $firstHostelId = ($hostelIds[0] === 'all' || $hostelIds[0] === 'null' || $hostelIds[0] === '') ? null : $hostelIds[0];
        $fee->update([
            'session_id' => $validated['session_id'],
            'hostel_id' => $firstHostelId,
            'amount' => $validated['amount'],
        ]);

        // Process any additional hostels selected in multi-select
        for ($i = 1; $i < count($hostelIds); $i++) {
            $hId = $hostelIds[$i];
            $targetHostelId = ($hId === 'all' || $hId === 'null' || $hId === '') ? null : $hId;

            HostelFee::updateOrCreate(
                [
                    'session_id' => $validated['session_id'],
                    'hostel_id' => $targetHostelId,
                ],
                [
                    'amount' => $validated['amount'],
                ]
            );
        }

        activity('hostel_fee')
            ->performedOn($fee)
            ->causedBy(auth()->user())
            ->withProperties([
                'amount' => $fee->amount,
                'session_id' => $fee->session_id,
                'hostel_id' => $fee->hostel_id,
            ])
            ->log("Hostel fee config updated to ₦" . number_format($fee->amount, 2));

        return back()->with('success', 'Hostel fee configuration updated successfully.');
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
