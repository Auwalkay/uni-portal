<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdmissionVerificationController extends Controller
{
    public function verify($identifier)
    {
        try {
            $identifier = decrypt($identifier);
        } catch (\Exception $e) {
            // If decryption fails, we might still want to try plain ID (for old letters if any)
            // but the user wants encrypted only. Let's stick to decryption.
            return Inertia::render('Public/VerifyAdmission', [
                'error' => 'Invalid or tampered verification link.'
            ]);
        }

        // Try finding by ID (UUID)
        $student = Student::with(['user', 'program', 'faculty'])
            ->where('id', $identifier)
            ->first();

        if ($student) {
            return Inertia::render('Public/VerifyAdmission', [
                'type' => 'student',
                'record' => [
                    'name' => $student->user->name,
                    'identifier' => $student->matriculation_number,
                    'program' => $student->program->name ?? 'N/A',
                    'faculty' => $student->faculty->name ?? 'N/A',
                    'session' => $student->admittedSession->name ?? 'N/A',
                    'status' => 'Verified Student',
                ]
            ]);
        }

        $applicant = Applicant::with(['user', 'programme.department.faculty'])
            ->where('id', $identifier)
            ->first();

        if ($applicant && $applicant->status === 'admitted') {
            return Inertia::render('Public/VerifyAdmission', [
                'type' => 'applicant',
                'record' => [
                    'name' => $applicant->user->name,
                    'identifier' => $applicant->jamb_registration_number ?? $applicant->application_number,
                    'program' => $applicant->programme->name ?? 'N/A',
                    'faculty' => $applicant->programme->department->faculty->name ?? 'N/A',
                    'session' => \App\Models\Session::current()->name ?? 'N/A',
                    'status' => 'Admitted Applicant',
                ]
            ]);
        }

        return Inertia::render('Public/VerifyAdmission', [
            'error' => 'Invalid admission record or verification failed.'
        ]);
    }
}
