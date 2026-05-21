<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\FrontDesk\Enquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $enquiries = Enquiry::with('receptionist')
            ->when($search, function ($query, $search) {
                $query->where('reference_id', 'like', "%{$search}%")
                    ->orWhere('inquirer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/FrontDesk/Enquiries/Index', [
            'enquiries' => $enquiries,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inquirer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'inquiry' => 'required|string',
        ]);

        Enquiry::create([
            ...$validated,
            'status' => 'open',
            'receptionist_id' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Enquiry recorded successfully.');
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'response' => 'nullable|string',
            'status' => 'required|in:open,closed',
        ]);

        $enquiry->update($validated);

        return redirect()->back()->with('success', 'Enquiry updated successfully.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        return redirect()->back()->with('success', 'Enquiry deleted.');
    }
}
