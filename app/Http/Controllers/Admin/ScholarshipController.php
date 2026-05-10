<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    public function index()
    {
        $scholarships = \App\Models\Scholarship::withCount(['students', 'applicants'])->latest()->get();
        return \Inertia\Inertia::render('Admin/Scholarships/Index', [
            'scholarships' => $scholarships
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'covers_admin_charges' => 'required|boolean',
            'covers_hostel_fees' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        \App\Models\Scholarship::create($validated);

        return redirect()->back()->with('success', 'Scholarship created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $scholarship = \App\Models\Scholarship::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'covers_admin_charges' => 'required|boolean',
            'covers_hostel_fees' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        // If percentage is changing, check for associated students/applicants
        if ((float) $request->percentage !== (float) $scholarship->percentage) {
            $hasUsers = \App\Models\Student::where('scholarship_id', $id)->exists() 
                     || \App\Models\Applicant::where('scholarship_id', $id)->exists();
            
            if ($hasUsers) {
                return redirect()->back()->with('error', 'The scholarship percentage cannot be modified once students or applicants have been assigned to it.');
            }
        }

        $scholarship->update($validated);

        return redirect()->back()->with('success', 'Scholarship updated successfully.');
    }

    public function destroy(string $id)
    {
        $scholarship = \App\Models\Scholarship::findOrFail($id);

        // Prevent deletion if students are using it
        if (\App\Models\Student::where('scholarship_id', $id)->exists() || \App\Models\Applicant::where('scholarship_id', $id)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete scholarship because it is associated with students or applicants.');
        }

        $scholarship->delete();

        return redirect()->back()->with('success', 'Scholarship deleted successfully.');
    }
}
