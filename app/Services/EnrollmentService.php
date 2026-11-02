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

            $currentSession = ($programme ? \App\Models\Session::currentForProgram($programme) : null)
                ?: \App\Models\Session::current();
            if (!$currentSession) {
                throw new \Exception('No active academic session found.');
            }

            $currenSemester = $currentSession->semesters()->where('is_current', true)->first();

            // Use the level the admin specified at admission; fall back to entry_mode/PG tier logic
            if ($applicant->admitted_level) {
                $currentLevel = (int) $applicant->admitted_level;
            } elseif ($programme && $programme->isPostgraduate()) {
                $currentLevel = app(\App\Services\Admissions\PostgraduateAdmissionService::class)->resolveDefaultLevel($programme);
            } else {
                $currentLevel = ($applicant->application_mode === 'DE') ? 200 : 100;
            }

            $matricNo = $programme
                ? \App\Helpers\MatriculationNumberHelper::generateForProgram($programme, [
                    'dept_code' => $programme->department?->code,
                    'level'     => $currentLevel,
                ])
                : \App\Helpers\MatriculationNumberHelper::generate([
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
                'gender'               => $applicant->gender ?? 'other',
                'dob'                  => $applicant->dob,
                'phone_number'         => $applicant->phone,
                'address'              => $applicant->address,
                'previous_institution' => $applicant->previous_institution,
                'passport_photo_path'  => $applicant->documents()->where('type', 'passport_photo')->value('path'),
                'entry_mode'           => $applicant->application_mode ?? ($programme?->isPostgraduate() ? 'POSTGRADUATE' : 'UTME'),
                'admitted_session_id'  => $currentSession->id,
                'program_duration'     => max(($programme?->duration ?? 4) - ($currentLevel === 200 ? 1 : ($currentLevel === 300 ? 2 : 0)), 1),
                'scholarship_id'       => $applicant->scholarship_id,
            ]);

            if ($programme?->isPostgraduate() || $applicant->isPostgraduate()) {
                $this->createPostgraduateStudentProfile($student, $applicant, $programme);
            }

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

    protected function createPostgraduateStudentProfile(Student $student, Applicant $applicant, ?\App\Models\Programme $programme): void
    {
        $year = date('Y');
        $sequence = str_pad((string) (\App\Models\PostgraduateStudentProfile::count() + 1), 4, '0', STR_PAD_LEFT);
        $spgsFileNumber = "SPGS/{$year}/{$sequence}";

        while (\App\Models\PostgraduateStudentProfile::where('spgs_file_number', $spgsFileNumber)->exists()) {
            $sequence = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            $spgsFileNumber = "SPGS/{$year}/{$sequence}";
        }

        $durationYears = max((int) ($programme?->duration ?? 2), 1);
        $minSemesters = $durationYears * 2;
        $maxSemesters = ($durationYears + 2) * 2;

        $qualificationsSnapshot = $applicant->qualifications()->get()->map(fn ($q) => [
            'qualification_type' => $q->qualification_type,
            'institution_name' => $q->institution_name,
            'course_of_study' => $q->course_of_study,
            'class_of_degree' => $q->class_of_degree,
            'cgpa' => $q->cgpa,
            'scale_max' => $q->scale_max,
            'graduation_year' => $q->graduation_year,
            'certificate_number' => $q->certificate_number,
            'is_highest_qualification' => $q->is_highest_qualification,
        ])->values()->all();

        $employmentsSnapshot = $applicant->employments()->get()->map(fn ($e) => [
            'employer_name' => $e->employer_name,
            'job_title' => $e->job_title,
            'department_or_unit' => $e->department_or_unit,
            'start_date' => $e->start_date?->toDateString(),
            'end_date' => $e->end_date?->toDateString(),
            'is_current' => $e->is_current,
            'responsibilities' => $e->responsibilities,
        ])->values()->all();

        \App\Models\PostgraduateStudentProfile::updateOrCreate(
            ['student_id' => $student->id],
            [
                'applicant_id' => $applicant->id,
                'spgs_file_number' => $spgsFileNumber,
                'study_mode' => $applicant->study_mode ?? 'full_time',
                'area_of_specialization' => $applicant->area_of_specialization,
                'sponsorship_type' => $applicant->sponsorship_type ?? 'self',
                'sponsor_name' => $applicant->sponsor_name,
                'marital_status' => $applicant->marital_status,
                'nationality' => $applicant->nationality ?? 'Nigerian',
                'nysc_status' => $applicant->nysc_status,
                'nysc_certificate_number' => $applicant->nysc_certificate_number,
                'highest_entry_qualification' => $applicant->previous_qualification,
                'entry_institution' => $applicant->previous_institution,
                'entry_course_of_study' => $applicant->previous_course_of_study,
                'entry_class_of_degree' => $applicant->previous_class_of_degree,
                'entry_cgpa' => $applicant->previous_cgpa,
                'entry_graduation_year' => $applicant->graduation_year,
                'prior_qualifications_snapshot' => $qualificationsSnapshot,
                'employment_history_snapshot' => $employmentsSnapshot,
                'work_experience_years' => (int) ($applicant->work_experience_years ?? 0),
                'official_transcript_status' => $applicant->official_transcript_status ?? 'not_requested',
                'min_duration_semesters' => $minSemesters,
                'max_duration_semesters' => $maxSemesters,
                'sessions_enrolled_count' => 1,
                'candidacy_status' => 'active_coursework',
                'candidacy_expires_at' => now()->addYears($durationYears + 2)->toDateString(),
            ]
        );
    }
}
