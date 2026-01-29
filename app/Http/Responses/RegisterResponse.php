<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        return redirect()->route('applicant.dashboard')
            ->with('success', 'Registration successful! Welcome to your dashboard.');
    }
}
