<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Student;
use App\Models\User;

class EnrollmentService
{
    public function enroll(Applicant $applicant, int|string $userId)
    {
        if (Student::where('user_id', $userId)->exists()) {
            return;
        }

        $year = date('Y');
        $facCode = $applicant->programme->department->faculty->code ?? 'GEN';
        $deptCode = $applicant->programme->department->code ?? 'GEN';

        $pattern = "{$year}/{$facCode}/{$deptCode}/%";
        $count = Student::where('matriculation_number', 'LIKE', $pattern)->count();
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $matricNo = "{$year}/{$facCode}/{$deptCode}/{$sequence}";

        $currentSession = \App\Models\Session::current();

        Student::create([
            'user_id' => $userId,
            'matriculation_number' => $matricNo,
            'program' => $applicant->programme->name ?? 'N/A',
            'department' => $applicant->programme->department->name ?? 'N/A',
            'faculty' => $applicant->programme->department->faculty->name ?? 'N/A',
            'current_level' => 100,
            'entry_mode' => $applicant->application_mode,
            'admitted_session_id' => $currentSession?->id,
            'program_duration' => $applicant->programme->duration ?? 4,
        ]);

        $user = User::find($userId);
        if ($user) {
            $user->assignRole('student');
        }

        $applicant->update(['status' => 'enrolled']);
    }
}
