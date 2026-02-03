<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        // Admin & Staff Roles
        if (
            $user->hasAnyRole([
                'admin',
                'registrar',
                'dean',
                'hod',
                'course_coordinator',
                'lecturer',
                'admissions_manager',
                'admissions_officer',
                'admissions_clerk',
                'bursar',
                'finance_officer',
                'finance_clerk'
            ])
        ) {
            return redirect()->route('admin.dashboard');
        }

        // Student Role
        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }

        // Applicant Role
        if ($user->hasRole('applicant')) {
            return redirect()->route('applicant.dashboard');
        }

        // Default to standard dashboard or home
        return redirect()->intended(config('fortify.home'));
    }
}
