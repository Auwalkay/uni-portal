<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class StaffProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $staff = $user->staff()
            ->with(['department.faculty', 'unit', 'state', 'lga'])
            ->firstOrFail();
            
        // Ensure user relation is set to the current user object
        $staff->setRelation('user', $user);

        return Inertia::render('Staff/Profile/Edit', [
            'staff' => $staff,
            'status' => session('status'),
        ]);
    }

    public function update(Request $request)
    {
        $staff = auth()->user()->staff()->firstOrFail();

        $validated = $request->validate([
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'specialization' => 'nullable|string|max:255',
            'research_interests' => 'nullable|string',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_address' => 'nullable|string',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'profile_photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_photo')) {
            $user = auth()->user();
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        $staff->update($request->only([
            'phone_number', 'address', 'specialization', 'research_interests',
            'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_address', 'next_of_kin_relationship'
        ]));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function show()
    {
        $user = auth()->user();
        $staff = $user->staff()
            ->with(['department.faculty', 'unit', 'state', 'lga'])
            ->firstOrFail();

        $staff->setRelation('user', $user);

        return Inertia::render('Staff/Profile/Show', [
            'staff' => $staff,
        ]);
    }
}
