<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ApplicantReferee;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Lga;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\State;
use App\Models\Student;
use App\Models\User;
use App\Services\Admissions\PostgraduateAdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgraduatePhaseThreeArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $applicantRole = Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $accessApplicant = Permission::firstOrCreate(['name' => 'access_applicant_portal', 'guard_name' => 'web']);
        $accessAdmin = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $viewApps = Permission::firstOrCreate(['name' => 'view_applications', 'guard_name' => 'web']);
        $reviewApps = Permission::firstOrCreate(['name' => 'review_applications', 'guard_name' => 'web']);

        $applicantRole->givePermissionTo($accessApplicant);
        $adminRole->givePermissionTo([$accessAdmin, $viewApps, $reviewApps]);
    }

    public function test_entry_mode_qualification_validation_matrix(): void
    {
        $service = app(PostgraduateAdmissionService::class);
        $faculty = Faculty::create(['name' => 'School of Postgraduate Studies', 'code' => 'SPGS', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC', 'is_active' => true]);

        $pgd = Programme::create([
            'department_id' => $dept->id,
            'name' => 'PGD Computer Science',
            'type' => 'PGD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 1,
            'is_active' => true,
        ]);

        $mba = Programme::create([
            'department_id' => $dept->id,
            'name' => 'Master of Business Administration (MBA)',
            'type' => 'MBA',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'duration' => 2,
            'is_active' => true,
        ]);

        $msc = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 2,
            'is_active' => true,
        ]);

        $phd = Programme::create([
            'department_id' => $dept->id,
            'name' => 'Ph.D Computer Science',
            'type' => 'PHD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 3,
            'is_active' => true,
        ]);

        // 1. PGD (700L): HND & Third Class accepted; Pass rejected
        $this->assertTrue($service->validateEntryQualification($pgd, 'HND', 'Lower Credit')['eligible']);
        $this->assertTrue($service->validateEntryQualification($pgd, 'B.Sc', 'Third Class')['eligible']);
        $this->assertFalse($service->validateEntryQualification($pgd, 'B.Sc', 'Pass')['eligible']);
        $this->assertSame(700, $service->resolveDefaultLevel($pgd));

        // 2. MBA/MIM (800L): Direct HND or Third Class rejected; PGD (>= 3.00) or B.Sc (Min 2:2) accepted
        $this->assertFalse($service->validateEntryQualification($mba, 'HND', 'Upper Credit')['eligible']);
        $this->assertFalse($service->validateEntryQualification($mba, 'B.Sc', 'Third Class')['eligible']);
        $this->assertTrue($service->validateEntryQualification($mba, 'PGD', 'Credit', 3.40)['eligible']);
        $this->assertFalse($service->validateEntryQualification($mba, 'PGD', 'Pass', 2.60)['eligible']);
        $this->assertTrue($service->validateEntryQualification($mba, 'B.Sc', 'Second Class Lower (2:2)')['eligible']);
        $this->assertSame(800, $service->resolveDefaultLevel($mba));

        // 3. M.Sc (800L): Min 2:2 B.Sc or PGD (>= 3.00) required; HND/Third Class rejected
        $this->assertFalse($service->validateEntryQualification($msc, 'HND', 'Distinction')['eligible']);
        $this->assertFalse($service->validateEntryQualification($msc, 'B.Sc', 'Third Class')['eligible']);
        $this->assertTrue($service->validateEntryQualification($msc, 'B.Sc', 'Second Class Lower (2:2)')['eligible']);
        $this->assertTrue($service->validateEntryQualification($msc, 'PGD', 'Distinction', 3.75)['eligible']);
        $this->assertSame(800, $service->resolveDefaultLevel($msc));

        // 4. Ph.D (900L): Requires M.Sc with CGPA >= 3.50 (3.00-3.49 flags M.Phil conversion)
        $this->assertFalse($service->validateEntryQualification($phd, 'B.Sc', 'First Class', 4.80)['eligible']);
        $this->assertFalse($service->validateEntryQualification($phd, 'MBA', 'Distinction', 4.20)['eligible']);

        $mphilConversion = $service->validateEntryQualification($phd, 'M.Sc', 'Merit', 3.25);
        $this->assertFalse($mphilConversion['eligible']);
        $this->assertTrue($mphilConversion['conversion_required']);

        $directPhd = $service->validateEntryQualification($phd, 'M.Sc', 'Distinction', 3.75);
        $this->assertTrue($directPhd['eligible']);
        $this->assertFalse($directPhd['conversion_required']);
        $this->assertSame(900, $service->resolveDefaultLevel($phd));
    }

    public function test_pg_applicant_submission_with_transcript_and_three_online_referees(): void
    {
        Storage::fake('public');

        $state = State::create(['name' => 'Lagos']);
        $lga = Lga::create(['state_id' => $state->id, 'name' => 'Ikeja']);

        $pgSession = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);
        Semester::create(['session_id' => $pgSession->id, 'name' => 'First Semester', 'is_current' => true]);

        $faculty = Faculty::create(['name' => 'Faculty of Science', 'code' => 'FSC', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC', 'is_active' => true]);

        $mscProgramme = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'matric_format' => 'MSC/{YEAR}/{SEQUENCE}',
            'duration' => 2,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('applicant');
        $this->actingAs($user);

        Applicant::create([
            'user_id' => $user->id,
            'application_mode' => 'PG',
            'program_choice_1' => $mscProgramme->id,
            'status' => 'draft',
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'paid_amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        // 1. Attempt ineligible Third Class application to M.Sc -> should fail validation
        $invalidResponse = $this->post(route('applicant.apply.store'), [
            'first_name' => 'Ibrahim',
            'last_name' => 'Musa',
            'dob' => '1998-05-12',
            'phone' => '08012345678',
            'gender' => 'male',
            'address' => '12 University Road',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Amina Musa',
            'next_of_kin_phone' => '08098765432',
            'next_of_kin_relationship' => 'Spouse',
            'programme_id' => $mscProgramme->id,
            'mode' => 'PG',
            'previous_institution' => 'Ahmadu Bello University',
            'previous_qualification' => 'B.Sc',
            'previous_class_of_degree' => 'Third Class',
            'previous_cgpa' => 2.10,
            'referees' => [
                ['name' => 'Prof. One', 'email' => 'one@abu.edu.ng', 'institution_or_organization' => 'ABU Zaria'],
                ['name' => 'Prof. Two', 'email' => 'two@abu.edu.ng', 'institution_or_organization' => 'ABU Zaria'],
                ['name' => 'Dr. Three', 'email' => 'three@abu.edu.ng', 'institution_or_organization' => 'ABU Zaria'],
            ],
        ]);
        $invalidResponse->assertSessionHasErrors('previous_qualification');

        // 2. Submit valid 2:1 B.Sc application with Transcript & 3 Referees
        $validResponse = $this->post(route('applicant.apply.store'), [
            'first_name' => 'Ibrahim',
            'last_name' => 'Musa',
            'dob' => '1998-05-12',
            'phone' => '08012345678',
            'gender' => 'male',
            'address' => '12 University Road',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Amina Musa',
            'next_of_kin_phone' => '08098765432',
            'next_of_kin_relationship' => 'Spouse',
            'programme_id' => $mscProgramme->id,
            'mode' => 'PG',
            'previous_institution' => 'Ahmadu Bello University',
            'previous_qualification' => 'B.Sc',
            'previous_course_of_study' => 'Computer Science',
            'previous_class_of_degree' => 'Second Class Upper (2:1)',
            'previous_cgpa' => 4.15,
            'graduation_year' => 2022,
            'nysc_status' => 'Completed',
            'research_proposal_title' => 'Distributed Consensus in Federated Learning',
            'passport_photo' => UploadedFile::fake()->create('passport.jpg', 100, 'image/jpeg'),
            'academic_transcript' => UploadedFile::fake()->create('transcript.pdf', 200, 'application/pdf'),
            'degree_certificate' => UploadedFile::fake()->create('degree.pdf', 150, 'application/pdf'),
            'referees' => [
                [
                    'name' => 'Prof. Sani Bello',
                    'email' => 'sani.bello@abu.edu.ng',
                    'phone' => '08030000001',
                    'institution_or_organization' => 'ABU Zaria',
                    'position_or_rank' => 'Professor',
                    'relationship' => 'Undergraduate Supervisor',
                ],
                [
                    'name' => 'Prof. Ngozi Okonjo',
                    'email' => 'ngozi@unilag.edu.ng',
                    'phone' => '08030000002',
                    'institution_or_organization' => 'University of Lagos',
                    'position_or_rank' => 'Head of Department',
                    'relationship' => 'Academic Advisor',
                ],
                [
                    'name' => 'Dr. Tunde Bakare',
                    'email' => 'tunde@techcorp.ng',
                    'phone' => '08030000003',
                    'institution_or_organization' => 'TechCorp Nigeria',
                    'position_or_rank' => 'Chief Technology Officer',
                    'relationship' => 'Employer',
                ],
            ],
        ]);

        $validResponse->assertRedirect(route('applicant.apply.show'));

        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($applicant->isPostgraduate());
        $this->assertSame('B.Sc', $applicant->previous_qualification);
        $this->assertSame('Second Class Upper (2:1)', $applicant->previous_class_of_degree);
        $this->assertEquals(4.15, $applicant->previous_cgpa);
        $this->assertCount(3, $applicant->referees);
        $this->assertFalse($applicant->hasCompletedRefereeRecommendations());

        $this->assertDatabaseHas('applicant_documents', [
            'applicant_id' => $applicant->id,
            'type' => 'academic_transcript',
        ]);

        // 3. Public Referees submit recommendations via their secure tokens
        foreach ($applicant->referees as $referee) {
            $this->assertNotEmpty($referee->token);
            $this->assertSame(64, strlen($referee->token));

            $submitRef = $this->post(route('referee.recommendation.store', $referee->token), [
                'position_or_rank' => $referee->position_or_rank,
                'relationship' => $referee->relationship,
                'academic_capacity_rating' => 5,
                'research_potential_rating' => 5,
                'character_integrity_rating' => 5,
                'communication_rating' => 4,
                'recommendation_strength' => 'strongly_recommend',
                'comments' => 'An outstanding researcher with strong analytical and ethical foundations.',
            ]);

            $submitRef->assertSessionHas('success');
            $referee->refresh();
            $this->assertTrue($referee->isSubmitted());
            $this->assertEquals(4.75, $referee->average_rating);
        }

        $this->assertTrue($applicant->fresh()->hasCompletedRefereeRecommendations());

        // 4. Admin admits PG applicant at 800 Level and applicant enrolls
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->put(route('admin.admissions.pg.update', $applicant->id), [
            'status' => 'admitted',
            'admitted_level' => '800',
            'admitted_programme_id' => $mscProgramme->id,
        ])->assertSessionHas('success');

        $applicant->refresh();
        $this->assertSame('admitted', $applicant->status);
        $this->assertSame('800', (string) $applicant->admitted_level);

        $this->actingAs($user)->post(route('applicant.accept.offer'))
            ->assertRedirect(route('student.dashboard'));

        $student = Student::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(800, $student->current_level);
        $this->assertSame('MSC/2026/001', $student->matriculation_number);
        $this->assertTrue($student->isPostgraduate());

        // Verify 1-to-1 PostgraduateStudentProfile record was automatically created
        $pgProfile = $student->pgProfile()->first();
        $this->assertNotNull($pgProfile);
        $this->assertStringStartsWith('SPGS/' . date('Y') . '/', $pgProfile->spgs_file_number);
        $this->assertSame('full_time', $pgProfile->study_mode);
        $this->assertSame('B.Sc', $pgProfile->highest_entry_qualification);
        $this->assertSame('active_coursework', $pgProfile->candidacy_status);
        $this->assertSame(1, $pgProfile->sessions_enrolled_count);
    }

    public function test_multi_degree_hnd_plus_pgd_progression_to_msc_and_transcript_label_pdf(): void
    {
        Storage::fake('public');

        $state = State::create(['name' => 'Oyo']);
        $lga = Lga::create(['state_id' => $state->id, 'name' => 'Ibadan North']);

        $pgSession = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);
        Semester::create(['session_id' => $pgSession->id, 'name' => 'First Semester', 'is_current' => true]);

        $faculty = Faculty::create(['name' => 'School of Postgraduate Studies', 'code' => 'SPGS', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC', 'is_active' => true]);

        $mscProgramme = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'matric_format' => 'MSC/{YEAR}/{SEQUENCE}',
            'duration' => 2,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('applicant');
        $this->actingAs($user);

        Applicant::create([
            'user_id' => $user->id,
            'application_mode' => 'PG',
            'program_choice_1' => $mscProgramme->id,
            'status' => 'draft',
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'paid_amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        // Submit multi-degree application: HND (Upper Credit) + PGD (CGPA 3.85) + Employment + Study Mode
        $response = $this->post(route('applicant.apply.store'), [
            'title' => 'Mrs.',
            'first_name' => 'Folake',
            'last_name' => 'Adeyemi',
            'dob' => '1994-08-20',
            'phone' => '08022223333',
            'gender' => 'female',
            'marital_status' => 'Married',
            'has_name_change' => true,
            'previous_full_name' => 'Folake Ogunleye',
            'address' => '25 Bodija Estate, Ibadan',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Kunle Adeyemi',
            'next_of_kin_phone' => '08033334444',
            'next_of_kin_relationship' => 'Spouse',
            'programme_id' => $mscProgramme->id,
            'mode' => 'PG',
            'study_mode' => 'part_time',
            'area_of_specialization' => 'Cybersecurity & Cryptography',
            'sponsorship_type' => 'tetfund',
            'sponsor_name' => 'TETFund Nigeria',
            'nysc_status' => 'Exempted',
            'nysc_certificate_number' => 'EX-2019-88412',
            'work_experience_years' => 5,
            'qualifications' => [
                [
                    'qualification_type' => 'HND',
                    'institution_name' => 'Yaba College of Technology',
                    'course_of_study' => 'Computer Technology',
                    'class_of_degree' => 'Upper Credit',
                    'cgpa' => 3.45,
                    'scale_max' => 4.00,
                    'graduation_year' => 2018,
                ],
                [
                    'qualification_type' => 'PGD',
                    'institution_name' => 'University of Ibadan',
                    'course_of_study' => 'Computer Science',
                    'class_of_degree' => 'Distinction',
                    'cgpa' => 3.85,
                    'scale_max' => 5.00,
                    'graduation_year' => 2023,
                ],
            ],
            'employments' => [
                [
                    'employer_name' => 'First Bank of Nigeria',
                    'job_title' => 'Information Security Analyst',
                    'department_or_unit' => 'IT Risk & Compliance',
                    'start_date' => '2020-02-01',
                    'is_current' => true,
                ],
            ],
            'referees' => [
                ['name' => 'Prof. A. O. Osofisan', 'email' => 'osofisan@ui.edu.ng', 'institution_or_organization' => 'University of Ibadan'],
                ['name' => 'Dr. B. K. Alese', 'email' => 'alese@futa.edu.ng', 'institution_or_organization' => 'FUTA'],
                ['name' => 'Mr. T. Danjuma', 'email' => 'danjuma@firstbank.ng', 'institution_or_organization' => 'First Bank'],
            ],
        ]);

        $response->assertRedirect(route('applicant.apply.show'));

        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();
        $this->assertCount(2, $applicant->qualifications);
        $this->assertCount(1, $applicant->employments);
        $this->assertSame('PGD', $applicant->previous_qualification);
        $this->assertSame('part_time', $applicant->study_mode);
        $this->assertSame('tetfund', $applicant->sponsorship_type);
        $this->assertTrue($applicant->has_name_change);

        // Download Official Transcript Request Label PDF
        $pdfResponse = $this->get(route('applicant.apply.transcript_label'));
        $pdfResponse->assertStatus(200);
        $this->assertSame('requested_by_applicant', $applicant->fresh()->official_transcript_status);
    }

    public function test_application_pathway_mode_strictly_segregates_pg_and_ug_programmes(): void
    {
        $faculty = Faculty::create(['name' => 'Faculty of Sciences', 'code' => 'SCI', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC', 'is_active' => true]);

        $ugProgramme = Programme::create([
            'department_id' => $dept->id,
            'name' => 'B.Sc Computer Science',
            'type' => 'UG',
            'academic_stream' => 'UG',
            'is_active' => true,
        ]);

        $pgProgramme = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'is_active' => true,
        ]);

        $state = State::create(['name' => 'Lagos']);
        $lga = Lga::create(['state_id' => $state->id, 'name' => 'Ikeja']);

        $user = User::factory()->create();
        $user->assignRole('applicant');
        $this->actingAs($user);

        Applicant::create([
            'user_id' => $user->id,
            'application_mode' => 'PG',
            'program_choice_1' => $pgProgramme->id,
            'status' => 'draft',
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'paid_amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        // 1. Choosing PG mode with a UG programme must be rejected
        $response1 = $this->post(route('applicant.apply.store'), [
            'mode' => 'PG',
            'programme_id' => $ugProgramme->id,
            'first_name' => 'Ahmad',
            'last_name' => 'Ibrahim',
            'dob' => '1995-01-01',
            'phone' => '08011112222',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'NOK',
            'next_of_kin_phone' => '08011113333',
            'next_of_kin_relationship' => 'Brother',
            'previous_qualification' => 'B.Sc',
            'previous_class_of_degree' => 'Second Class Upper (2:1)',
            'previous_institution' => 'University of Lagos',
            'referees' => [
                ['name' => 'Ref 1', 'email' => 'r1@test.com', 'institution_or_organization' => 'Org 1'],
                ['name' => 'Ref 2', 'email' => 'r2@test.com', 'institution_or_organization' => 'Org 2'],
                ['name' => 'Ref 3', 'email' => 'r3@test.com', 'institution_or_organization' => 'Org 3'],
            ],
        ]);
        $response1->assertSessionHasErrors('programme_id');

        // 2. Choosing UTME mode with a PG programme must be rejected
        $response2 = $this->post(route('applicant.apply.store'), [
            'mode' => 'UTME',
            'programme_id' => $pgProgramme->id,
            'first_name' => 'Ahmad',
            'last_name' => 'Ibrahim',
            'dob' => '1995-01-01',
            'phone' => '08011112222',
            'jamb_score' => 280,
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'NOK',
            'next_of_kin_phone' => '08011113333',
            'next_of_kin_relationship' => 'Brother',
        ]);
        $response2->assertSessionHasErrors('programme_id');
    }
}
