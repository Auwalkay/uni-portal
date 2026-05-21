<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\FrontDesk\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $visitors = Visitor::with('receptionist')
            ->when($search, function ($query, $search) {
                $query->where('reference_id', 'like', "%{$search}%")
                    ->orWhere('visitor_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whom_to_see', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/FrontDesk/Visitors/Index', [
            'visitors' => $visitors,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'purpose' => 'required|string|max:255',
            'whom_to_see' => 'nullable|string|max:255',
        ]);

        Visitor::create([
            ...$validated,
            'check_in' => now(),
            'receptionist_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Visitor checked in successfully.');
    }

    public function update(Request $request, Visitor $visitor)
    {
        if ($request->has('check_out')) {
            $visitor->update(['check_out' => now()]);
            return redirect()->back()->with('success', 'Visitor checked out successfully.');
        }

        $validated = $request->validate([
            'visitor_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'purpose' => 'required|string|max:255',
            'whom_to_see' => 'nullable|string|max:255',
        ]);

        $visitor->update($validated);

        return redirect()->back()->with('success', 'Visitor updated successfully.');
    }

    public function destroy(Visitor $visitor)
    {
        $visitor->delete();
        return redirect()->back()->with('success', 'Visitor record deleted.');
    }
}
