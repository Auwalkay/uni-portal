<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $student = \App\Models\Student::where('user_id', $request->user()->id)->with('user')->firstOrFail();

        return \Inertia\Inertia::render('Student/Profile/Edit', [
            'student' => $student,
            'user' => $request->user(),
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
            'state_of_origin' => 'required|string',
            'lga' => 'required|string',
            'next_of_kin_name' => 'required|string',
            'next_of_kin_phone' => 'required|string',
            'next_of_kin_address' => 'nullable|string',
        ]);

        $student = \App\Models\Student::where('user_id', $request->user()->id)->firstOrFail();

        $student->update([
            'gender' => $request->gender,
            'dob' => $request->dob,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'state_of_origin' => $request->state_of_origin,
            'lga' => $request->lga,
            'next_of_kin_name' => $request->next_of_kin_name,
            'next_of_kin_phone' => $request->next_of_kin_phone,
            'next_of_kin_address' => $request->input('next_of_kin_address'),
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Profile updated successfully.');
    }
}
