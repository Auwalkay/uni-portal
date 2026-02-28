<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionTierController extends Controller
{
    public function index()
    {
        $tiers = SubscriptionTier::orderBy('max_students', 'asc')->get();

        return Inertia::render('Central/SubscriptionTiers/Index', [
            'tiers' => $tiers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        SubscriptionTier::create($validated);

        return back()->with('success', 'Subscription tier created successfully.');
    }

    public function update(Request $request, SubscriptionTier $subscriptionTier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $subscriptionTier->update($validated);

        return back()->with('success', 'Subscription tier updated successfully.');
    }

    public function destroy(SubscriptionTier $subscriptionTier)
    {
        $subscriptionTier->delete();

        return back()->with('success', 'Subscription tier deleted successfully.');
    }
}
