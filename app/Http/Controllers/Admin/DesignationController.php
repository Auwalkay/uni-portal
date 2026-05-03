<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class DesignationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Designation/Index', [
            'designations' => Designation::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:designations,name',
            'is_active' => 'boolean',
        ]);

        Designation::create($validated);

        Cache::forget('staff_designations_list');

        return back()->with('success', 'Designation created successfully.');
    }

    public function update(Request $request, Designation $designation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:designations,name,' . $designation->id,
            'is_active' => 'boolean',
        ]);

        $designation->update($validated);

        Cache::forget('staff_designations_list');

        return back()->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation)
    {
        // Check if designation is used by any staff (future proofing)
        // For now, just delete
        $designation->delete();

        Cache::forget('staff_designations_list');

        return back()->with('success', 'Designation deleted successfully.');
    }
}
