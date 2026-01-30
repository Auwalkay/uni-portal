<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $student = \App\Models\Student::where('user_id', $request->user()->id)
            ->with(['user', 'state', 'lga'])
            ->firstOrFail();

        $states = \App\Models\State::with('lgas')->orderBy('name')->get();

        return \Inertia\Inertia::render('Student/Profile/Edit', [
            'student' => $student,
            'user' => $request->user(),
            'states' => $states,
            'status' => session('status'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'gender' => 'required|string',
            'dob' => 'required|date',
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'state_id' => 'required|exists:states,id',
            'lga_id' => 'required|exists:lgas,id',
            'next_of_kin_name' => 'required|string',
            'next_of_kin_phone' => 'required|string',
            'next_of_kin_address' => 'nullable|string',
            'passport_photograph' => 'nullable|image|max:2048', // 2MB Max
            'indigene_letter' => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $student = \App\Models\Student::where('user_id', $request->user()->id)->firstOrFail();

        $data = [
            'gender' => $request->gender,
            'dob' => $request->dob,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'state_id' => $request->state_id,
            'lga_id' => $request->lga_id,
            'next_of_kin_name' => $request->next_of_kin_name,
            'next_of_kin_phone' => $request->next_of_kin_phone,
            'next_of_kin_address' => $request->input('next_of_kin_address'),
        ];

        if ($request->hasFile('passport_photograph')) {
            $path = $request->file('passport_photograph')->store('profile-photos', 'public');
            $data['passport_photo_path'] = $path;
        }

        if ($request->hasFile('indigene_letter')) {
            $path = $request->file('indigene_letter')->store('documents/indigene', 'public');
            $data['indigene_letter_path'] = $path;
        }

        $student->update($data);

        return redirect()->route('student.profile.edit')->with('status', 'Profile updated successfully.');
    }
}
