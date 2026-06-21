<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScholarshipController extends Controller
{
    public function index()
    {
        $scholarships = Scholarship::withCount(['students', 'applicants'])->latest()->get();

        return Inertia::render('Admin/Scholarships/Index', [
            'scholarships' => $scholarships,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:percentage,fixed',
            'percentage' => 'required_if:type,percentage|nullable|numeric|min:0|max:100',
            'amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'covers_admin_charges' => 'required|boolean',
            'covers_hostel_fees' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        if ($validated['type'] === 'fixed') {
            $validated['percentage'] = 0;
        } else {
            $validated['amount'] = 0;
        }

        Scholarship::create($validated);

        return redirect()->back()->with('success', 'Scholarship created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $scholarship = Scholarship::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:percentage,fixed',
            'percentage' => 'required_if:type,percentage|nullable|numeric|min:0|max:100',
            'amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'covers_admin_charges' => 'required|boolean',
            'covers_hostel_fees' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        if ($validated['type'] === 'fixed') {
            $validated['percentage'] = 0;
        } else {
            $validated['amount'] = 0;
        }

        // If percentage, amount or type is changing, check for associated students/applicants
        $percentageChanged = (float) ($validated['percentage'] ?? 0) !== (float) $scholarship->percentage;
        $amountChanged = (float) ($validated['amount'] ?? 0) !== (float) $scholarship->amount;
        $typeChanged = $validated['type'] !== $scholarship->type;

        if ($percentageChanged || $amountChanged || $typeChanged) {
            $hasUsers = \App\Models\Student::where('scholarship_id', $id)->exists()
                     || \App\Models\Applicant::where('scholarship_id', $id)->exists();

            if ($hasUsers) {
                return redirect()->back()->with('error', 'The scholarship configuration (percentage, amount, or type) cannot be modified once students or applicants have been assigned to it.');
            }
        }

        $scholarship->update($validated);

        return redirect()->back()->with('success', 'Scholarship updated successfully.');
    }

    public function destroy(string $id)
    {
        $scholarship = Scholarship::findOrFail($id);

        // Prevent deletion if students are using it
        if (\App\Models\Student::where('scholarship_id', $id)->exists() || \App\Models\Applicant::where('scholarship_id', $id)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete scholarship because it is associated with students or applicants.');
        }

        $scholarship->delete();

        return redirect()->back()->with('success', 'Scholarship deleted successfully.');
    }
}
