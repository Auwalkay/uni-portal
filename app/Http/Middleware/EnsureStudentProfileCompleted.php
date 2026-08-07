<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->hasRole('student')) {
            $student = $user->student;
            if ($student) {
                // Determine if any required field is null or O-Level result does not exist
                $hasState = !empty($student->state_id);
                $hasLga = !empty($student->lga_id);
                $hasGender = !empty($student->gender);
                $hasJamb = !empty($student->jamb_registration_number);
                $hasPassport = !empty($student->passport_photo_path);
                $hasIndigene = !empty($student->indigene_letter_path);
                $hasOlevel = $student->oLevelResults()->exists();

                if (!$hasState || !$hasLga || !$hasGender || !$hasJamb || !$hasPassport || !$hasIndigene || !$hasOlevel) {
                    $currentRoute = $request->route()->getName();
                    if ($currentRoute !== 'student.profile.edit' && $currentRoute !== 'student.profile.update') {
                        return redirect()->route('student.profile.edit')
                            ->with('warning', 'You must complete your profile details (Gender, State of Origin, LGA, JAMB Registration Number, Passport Photo, Indigene Letter, and O-Level sittings) to proceed.');
                    }
                }
            }
        }

        return $next($request);
    }
}
