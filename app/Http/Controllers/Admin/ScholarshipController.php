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
        $feeTypes = \App\Models\FeeType::select('id', 'name', 'slug', 'is_one_time')->orderBy('name')->get();

        $totalStudentsOnScholarship = $scholarships->sum('students_count');
        $totalApplicantsOnScholarship = $scholarships->sum('applicants_count');
        $totalBeneficiaries = $totalStudentsOnScholarship + $totalApplicantsOnScholarship;

        $summaryStats = [
            'total_schemes' => $scholarships->count(),
            'active_schemes' => $scholarships->where('is_active', true)->count(),
            'inactive_schemes' => $scholarships->where('is_active', false)->count(),
            'total_students' => $totalStudentsOnScholarship,
            'total_applicants' => $totalApplicantsOnScholarship,
            'total_beneficiaries' => $totalBeneficiaries,
        ];

        return Inertia::render('Admin/Scholarships/Index', [
            'scholarships' => $scholarships,
            'feeTypes' => $feeTypes,
            'summaryStats' => $summaryStats,
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
            'covers_all_expenses' => 'nullable|boolean',
            'waives_late_fees' => 'nullable|boolean',
            'excluded_fee_type_ids' => 'nullable|array',
            'excluded_fee_type_ids.*' => 'exists:fee_types,id',
            'is_active' => 'required|boolean',
        ]);

        $validated['covers_all_expenses'] = (bool) ($validated['covers_all_expenses'] ?? false);
        $validated['waives_late_fees'] = array_key_exists('waives_late_fees', $validated) ? (bool) $validated['waives_late_fees'] : true;

        if ($validated['type'] === 'fixed') {
            $validated['percentage'] = 0;
        } else {
            $validated['amount'] = 0;
        }

        if ($validated['covers_all_expenses']) {
            $validated['percentage'] = 100;
            $validated['type'] = 'percentage';
            $validated['amount'] = 0;
        } else {
            $validated['excluded_fee_type_ids'] = [];
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
            'covers_all_expenses' => 'nullable|boolean',
            'waives_late_fees' => 'nullable|boolean',
            'excluded_fee_type_ids' => 'nullable|array',
            'excluded_fee_type_ids.*' => 'exists:fee_types,id',
            'is_active' => 'required|boolean',
        ]);

        $validated['covers_all_expenses'] = (bool) ($validated['covers_all_expenses'] ?? false);
        $validated['waives_late_fees'] = array_key_exists('waives_late_fees', $validated) ? (bool) $validated['waives_late_fees'] : true;

        if ($validated['type'] === 'fixed') {
            $validated['percentage'] = 0;
        } else {
            $validated['amount'] = 0;
        }

        if ($validated['covers_all_expenses']) {
            $validated['percentage'] = 100;
            $validated['type'] = 'percentage';
            $validated['amount'] = 0;
        } else {
            $validated['excluded_fee_type_ids'] = [];
        }

        // If percentage, amount, type, or coverage mode is changing, check for associated students/applicants
        $percentageChanged = (float) ($validated['percentage'] ?? 0) !== (float) $scholarship->percentage;
        $amountChanged = (float) ($validated['amount'] ?? 0) !== (float) $scholarship->amount;
        $typeChanged = $validated['type'] !== $scholarship->type;
        $coverageChanged = (bool) ($validated['covers_all_expenses'] ?? false) !== (bool) $scholarship->covers_all_expenses;

        if ($percentageChanged || $amountChanged || $typeChanged || $coverageChanged) {
            $hasUsers = \App\Models\Student::where('scholarship_id', $id)->exists()
                     || \App\Models\Applicant::where('scholarship_id', $id)->exists();

            if ($hasUsers) {
                return redirect()->back()->with('error', 'The scholarship configuration (percentage, amount, type, or coverage mode) cannot be modified once students or applicants have been assigned to it.');
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
