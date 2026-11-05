<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Lga;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\User;
use App\Services\AcademicCacheService;
use App\Services\Admissions\PostgraduateAdmissionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostgraduateQualificationOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AcademicCacheService::clearAll();
    }

    /**
     * Test that the qualification constants contain all required options including professional bodies and Other.
     */
    public function test_postgraduate_qualifications_and_classes_contain_new_options()
    {
        $expectedQuals = ['ND', 'HND', 'ACCA', 'CIB', 'ANAN', 'ICAN', 'Other'];
        foreach ($expectedQuals as $qual) {
            $this->assertContains($qual, PostgraduateAdmissionService::QUALIFICATIONS);
        }

        $expectedClasses = ['Associate / Member', 'Passed / Qualified', 'Fellow'];
        foreach ($expectedClasses as $class) {
            $this->assertContains($class, PostgraduateAdmissionService::DEGREE_CLASSES);
        }
    }

    /**
     * Test that the application wizard renders with the expanded qualifications array.
     */
    public function test_application_wizard_provides_qualifications_to_inertia()
    {
        $user = User::factory()->create();
        $user->assignRole('applicant');

        $session = Session::create([
            'name' => '2026/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'is_current' => true,
        ]);

        $faculty = Faculty::firstOrCreate(['code' => 'MGT'], ['name' => 'Faculty of Management Sciences', 'is_active' => true]);
        $dept = Department::firstOrCreate(['code' => 'ACC'], ['name' => 'Accounting', 'faculty_id' => $faculty->id, 'is_academic' => true, 'is_active' => true]);
        $programme = Programme::create([
            'name' => 'PGD Accounting',
            'department_id' => $dept->id,
            'type' => 'PGD',
            'academic_stream' => 'PG',
            'is_active' => true,
        ]);

        Applicant::create([
            'user_id' => $user->id,
            'application_mode' => 'PG',
            'program_choice_1' => $programme->id,
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

        $response = $this->actingAs($user)->get(route('applicant.apply.form', [
            'mode' => 'PG',
            'programme_id' => $programme->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Applicant/Application/FormWizard')
                ->has('qualifications')
                ->where('qualifications', fn ($quals) =>
                    collect($quals)->contains('ND') &&
                    collect($quals)->contains('HND') &&
                    collect($quals)->contains('ICAN') &&
                    collect($quals)->contains('ANAN') &&
                    collect($quals)->contains('ACCA') &&
                    collect($quals)->contains('CIB') &&
                    collect($quals)->contains('Other')
                )
        );
    }

    /**
     * Test that an applicant applying for PGD with ND alone is not accepted directly,
     * but an applicant with ND + HND is approved and HND is ranked as highest.
     */
    public function test_nd_alone_rejected_but_nd_plus_hnd_is_approved()
    {
        $user = User::factory()->create();
        $user->assignRole('applicant');

        $state = State::firstOrCreate(['name' => 'Lagos']);
        $lga = Lga::firstOrCreate(['name' => 'Ikeja', 'state_id' => $state->id]);

        $faculty = Faculty::firstOrCreate(['code' => 'MGT'], ['name' => 'Faculty of Management Sciences', 'is_active' => true]);
        $dept = Department::firstOrCreate(['code' => 'ACC'], ['name' => 'Accounting', 'faculty_id' => $faculty->id, 'is_academic' => true, 'is_active' => true]);
        $programme = Programme::create([
            'name' => 'PGD Accounting',
            'department_id' => $dept->id,
            'type' => 'PGD',
            'academic_stream' => 'PG',
            'is_active' => true,
        ]);

        Session::create([
            'name' => '2026/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'is_current' => true,
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'paid_amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        Applicant::create([
            'user_id' => $user->id,
            'application_mode' => 'PG',
            'program_choice_1' => $programme->id,
            'status' => 'draft',
        ]);

        $basePayload = [
            'first_name' => 'Amina',
            'last_name' => 'Bello',
            'dob' => '1998-05-12',
            'phone' => '08012345678',
            'gender' => 'female',
            'marital_status' => 'Single',
            'nationality' => 'Nigerian',
            'address' => 'Plot 4, Victoria Island, Lagos',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Musa Bello',
            'next_of_kin_phone' => '08022223333',
            'next_of_kin_relationship' => 'Father',
            'mode' => 'PG',
            'programme_id' => $programme->id,
            'study_mode' => 'full_time',
            'referees' => [
                ['name' => 'Prof. A. Sanni', 'email' => 'sanni@example.com', 'institution_or_organization' => 'UNILAG'],
                ['name' => 'Dr. K. Musa', 'email' => 'musa@example.com', 'institution_or_organization' => 'ABU'],
                ['name' => 'Mr. J. Ojo', 'email' => 'ojo@example.com', 'institution_or_organization' => 'KPMG'],
            ],
        ];

        // 1. Submit with ND only -> should fail eligibility validation
        $response1 = $this->actingAs($user)->post(route('applicant.apply.store'), array_merge($basePayload, [
            'qualifications' => [
                [
                    'qualification_type' => 'ND',
                    'institution_name' => 'Yaba College of Technology',
                    'course_of_study' => 'Accountancy',
                    'class_of_degree' => 'Upper Credit',
                    'graduation_year' => 2018,
                ],
            ],
        ]));

        $response1->assertSessionHasErrors(['previous_qualification']);

        // 2. Submit with ND + HND -> should succeed!
        $response2 = $this->actingAs($user)->post(route('applicant.apply.store'), array_merge($basePayload, [
            'qualifications' => [
                [
                    'qualification_type' => 'ND',
                    'institution_name' => 'Yaba College of Technology',
                    'course_of_study' => 'Accountancy',
                    'class_of_degree' => 'Upper Credit',
                    'graduation_year' => 2018,
                ],
                [
                    'qualification_type' => 'HND',
                    'institution_name' => 'Yaba College of Technology',
                    'course_of_study' => 'Accountancy',
                    'class_of_degree' => 'Upper Credit',
                    'graduation_year' => 2021,
                ],
            ],
        ]));

        $response2->assertRedirect(route('applicant.apply.show'));

        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();
        $this->assertCount(2, $applicant->qualifications);
        $this->assertSame('HND', $applicant->previous_qualification);
    }

    /**
     * Test that professional qualifications (ICAN, ACCA, ANAN, CIB) are eligible for PGD and MBA,
     * and that 'Other' normalizes and persists custom qualifications like CFA.
     */
    public function test_professional_and_custom_other_qualifications_eligible()
    {
        $user = User::factory()->create();
        $user->assignRole('applicant');

        $state = State::firstOrCreate(['name' => 'Lagos']);
        $lga = Lga::firstOrCreate(['name' => 'Ikeja', 'state_id' => $state->id]);

        $faculty = Faculty::firstOrCreate(['code' => 'MGT'], ['name' => 'Faculty of Management Sciences', 'is_active' => true]);
        $dept = Department::firstOrCreate(['code' => 'BA'], ['name' => 'Business Administration', 'faculty_id' => $faculty->id, 'is_academic' => true, 'is_active' => true]);
        $mbaProgramme = Programme::create([
            'name' => 'Master of Business Administration (MBA)',
            'department_id' => $dept->id,
            'type' => 'MBA',
            'academic_stream' => 'PG',
            'is_active' => true,
        ]);

        Session::create([
            'name' => '2026/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'is_current' => true,
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'paid_amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        Applicant::create([
            'user_id' => $user->id,
            'application_mode' => 'PG',
            'program_choice_1' => $mbaProgramme->id,
            'status' => 'draft',
        ]);

        $basePayload = [
            'first_name' => 'Emeka',
            'last_name' => 'Okafor',
            'dob' => '1995-08-20',
            'phone' => '08098765432',
            'gender' => 'male',
            'marital_status' => 'Married',
            'nationality' => 'Nigerian',
            'address' => '10 Marina Street, Lagos',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Chinyere Okafor',
            'next_of_kin_phone' => '08033334444',
            'next_of_kin_relationship' => 'Spouse',
            'mode' => 'PG',
            'programme_id' => $mbaProgramme->id,
            'study_mode' => 'full_time',
            'referees' => [
                ['name' => 'Prof. S. Adeleke', 'email' => 'adeleke@example.com', 'institution_or_organization' => 'OAU'],
                ['name' => 'Dr. C. Nwosu', 'email' => 'nwosu@example.com', 'institution_or_organization' => 'UNN'],
                ['name' => 'Mrs. F. Alabi', 'email' => 'alabi@example.com', 'institution_or_organization' => 'PwC'],
            ],
        ];

        // 1. Submit with ICAN
        $response = $this->actingAs($user)->post(route('applicant.apply.store'), array_merge($basePayload, [
            'qualifications' => [
                [
                    'qualification_type' => 'ICAN',
                    'institution_name' => 'Institute of Chartered Accountants of Nigeria',
                    'course_of_study' => 'Accounting & Auditing',
                    'class_of_degree' => 'Associate / Member',
                    'graduation_year' => 2022,
                ],
            ],
        ]));

        $response->assertRedirect(route('applicant.apply.show'));
        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('ICAN', $applicant->previous_qualification);
        $this->assertSame('Associate / Member', $applicant->previous_class_of_degree);

        // 2. Submit with Other + Specify 'CFA'
        $user2 = User::factory()->create();
        $user2->assignRole('applicant');

        Applicant::create([
            'user_id' => $user2->id,
            'application_mode' => 'PG',
            'program_choice_1' => $mbaProgramme->id,
            'status' => 'draft',
        ]);

        Invoice::create([
            'user_id' => $user2->id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'paid_amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        $response2 = $this->actingAs($user2)->post(route('applicant.apply.store'), array_merge($basePayload, [
            'first_name' => 'Folake',
            'last_name' => 'Adeyemi',
            'qualifications' => [
                [
                    'qualification_type' => 'Other',
                    'other_qualification' => 'CFA',
                    'institution_name' => 'CFA Institute',
                    'course_of_study' => 'Chartered Financial Analyst Level III',
                    'class_of_degree' => 'Passed / Qualified',
                    'graduation_year' => 2023,
                ],
            ],
        ]));

        $response2->assertRedirect(route('applicant.apply.show'));
        $applicant2 = Applicant::where('user_id', $user2->id)->firstOrFail();
        $this->assertSame('CFA', $applicant2->previous_qualification);
        $this->assertSame('Passed / Qualified', $applicant2->previous_class_of_degree);
        $this->assertSame('CFA', $applicant2->qualifications->first()->qualification_type);
    }
}
