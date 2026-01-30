<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IdCardController extends Controller
{
    public function show(Request $request)
    {
        $student = \App\Models\Student::where('user_id', $request->user()->id)
            ->with(['user', 'academicDepartment.faculty', 'admittedSession'])
            ->firstOrFail();

        // Ensure user has paid school fees
        $hasPaidSchoolFee = \App\Models\Invoice::where('user_id', $request->user()->id)
            ->where('type', 'school_fee')
            ->where('status', 'paid')
            ->exists();

        if (!$hasPaidSchoolFee) {
            return redirect()->route('student.dashboard')->with('error', 'You must pay school fees to access your ID Card.');
        }

        if (!$student->passport_photo_path) {
            return redirect()->route('student.profile.edit')->with('error', 'You must upload a passport photograph to access your ID Card.');
        }

        return Inertia::render('Student/IdCard', [
            'student' => $student,
        ]);
    }
}
