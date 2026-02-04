<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\FrontDesk\Complaint;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $complaints = Complaint::with('receptionist')
            ->when($search, function ($query, $search) {
                $query->where('reference_id', 'like', "%{$search}%")
                    ->orWhere('complainant_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/FrontDesk/Complaints/Index', [
            'complaints' => $complaints,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'complainant_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Complaint::create([
            ...$validated,
            'status' => 'pending',
            'receptionist_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Complaint recorded successfully.');
    }

    public function update(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,resolved',
            'resolution_notes' => 'nullable|string',
        ]);

        $complaint->update($validated);

        return redirect()->back()->with('success', 'Complaint updated successfully.');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();
        return redirect()->back()->with('success', 'Complaint deleted.');
    }
}
