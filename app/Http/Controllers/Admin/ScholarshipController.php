<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ScholarshipController extends Controller
{
    public function index()
    {
        $scholarships = \App\Models\Scholarship::latest()->get();
        return \Inertia\Inertia::render('Admin/Scholarships/Index', [
            'scholarships' => $scholarships
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
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
        ]);

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
