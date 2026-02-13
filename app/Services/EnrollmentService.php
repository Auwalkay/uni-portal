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
            $facCode = $applicant->programme?->department?->faculty?->code ?? 'GEN';
            $deptCode = $applicant->programme?->department?->code ?? 'GEN';

            $currentSession = \App\Models\Session::current();
            if (! $currentSession) {
                throw new \Exception('No active academic session found.');
            }

            $currenSemester = $currentSession->semesters()->where('is_current', true)->first();

            $currentLevel = ($applicant->application_mode === 'DE') ? 200 : 100;

            /**
             * Concurrency-safe matric generation:
             * - Get latest matric for this pattern (ORDER BY) and lock it.
             * - Then increment sequence.
             */
            $prefix = "{$year}/{$facCode}/{$deptCode}/";

            $last = Student::where('matriculation_number', 'LIKE', $prefix.'%')
                ->orderBy('matriculation_number', 'desc')
                ->lockForUpdate()
                ->value('matriculation_number');

            $lastSeq = 0;
            if ($last) {
                $parts = explode('/', $last);
                $lastSeq = (int) end($parts);
            }

            $sequence = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
            $matricNo = $prefix.$sequence;

            $student = Student::create([
                'user_id' => $userId,
                'matriculation_number' => $matricNo,
                'program_id' => $applicant->programme?->id,
                'department_id' => $applicant->programme?->department?->id,
                'faculty_id' => $applicant->programme?->department?->faculty?->id,
                'state_id' => $applicant->state_id,
                'lga_id' => $applicant->lga_id,
                'current_level' => $currentLevel,
                'gender' => $applicant->gender,
                'entry_mode' => $applicant->application_mode,
                'admitted_session_id' => $currentSession->id,
                'program_duration' => $applicant->programme?->duration ?? 4,
            ]);

            StudentSession::create([
                'student_id' => $student->id,
                'session_id' => $currentSession->id,
                'level' => $currentLevel,
                'status' => 'active',
                'semester' => $currenSemester->name,
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
