<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Student;
use App\Models\StudentSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function enroll(Applicant $applicant, int|string $userId): void
    {
        DB::transaction(function () use ($applicant, $userId) {

            // Lock the student row check so two concurrent requests don't both pass
            $alreadyStudent = Student::where('user_id', $userId)
                ->lockForUpdate()
                ->exists();

            if ($alreadyStudent) {
                return;
            }

            $year = date('y');

            // ── Use the admin-specified admission details ──────────────────────
            // Load the admitted programme (may differ from their application choice)
            $admittedProgramme = $applicant->admitted_programme_id
                ? \App\Models\Programme::with('department.faculty')->find($applicant->admitted_programme_id)
                : null;

            // Fall back to their programme choice if admin didn't override
            $programme = $admittedProgramme ?? $applicant->programme;

            $facCode  = $programme?->department?->faculty?->code ?? 'GEN';
            $deptCode = $programme?->department?->code ?? 'GEN';

            $currentSession = \App\Models\Session::current();
            if (!$currentSession) {
                throw new \Exception('No active academic session found.');
            }

            $currenSemester = $currentSession->semesters()->where('is_current', true)->first();

            // Use the level the admin specified at admission; fall back to entry_mode logic
            $currentLevel = $applicant->admitted_level
                ? (int) $applicant->admitted_level
                : (($applicant->application_mode === 'DE') ? 200 : 100);

            $matricNo = \App\Helpers\MatriculationNumberHelper::generate([
                'dept_code' => $programme?->department?->code,
                'level'     => $currentLevel,
            ]);

            $student = Student::create([
                'user_id'              => $userId,
                'matriculation_number' => $matricNo,
                'program_id'           => $programme?->id,
                'department_id'        => $programme?->department?->id,
                'faculty_id'           => $programme?->department?->faculty?->id,
                'state_id'             => $applicant->state_id,
                'lga_id'               => $applicant->lga_id,
                'current_level'        => $currentLevel,
                'gender'               => $applicant->gender,
                'entry_mode'           => $applicant->application_mode,
                'admitted_session_id'  => $currentSession->id,
                'program_duration'     => max(($programme?->duration ?? 4) - ($currentLevel === 200 ? 1 : ($currentLevel === 300 ? 2 : 0)), 1),
                'scholarship_id'       => $applicant->scholarship_id,
            ]);

            StudentSession::create([
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
                'level' => $currentLevel,
                'status' => 'active',
                'semester' => $currenSemester ? $currenSemester->name : '1st Semester',
            ]);

            $user = User::lockForUpdate()->find($userId);
            if ($user) {
                $user->assignRole('student');
                $user->removeRole('applicant');
            }

            $applicant->update(['status' => 'enrolled']);
        });
    }
}
