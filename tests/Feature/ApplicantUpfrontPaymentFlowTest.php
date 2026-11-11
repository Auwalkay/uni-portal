<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicantUpfrontPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;
    private Department $dept;
    private Programme $ugProg;
    private Programme $pgProg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Faculty::create(['name' => 'Faculty of Computing', 'code' => 'FOC']);
        $this->dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $this->faculty->id]);

        $this->ugProg = Programme::create([
            'name' => 'B.Sc. Computer Science',
            'department_id' => $this->dept->id,
            'type' => 'UG',
            'duration' => 4,
            'is_active' => true,
        ]);

        $this->pgProg = Programme::create([
            'name' => 'M.Sc. Computer Science',
            'department_id' => $this->dept->id,
            'type' => 'MSC',
            'duration' => 2,
            'is_active' => true,
        ]);

        Role::firstOrCreate(['name' => 'applicant']);
        Permission::firstOrCreate(['name' => 'access_applicant_portal']);

        \App\Models\Session::create([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);
    }

    private function createApplicantUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('access_applicant_portal');
        return $user;
    }

    public function test_applicant_selects_programme_generates_invoice_and_redirects_to_payment()
    {
        $applicantUser = $this->createApplicantUser();

        $response = $this->actingAs($applicantUser)->post(route('applicant.apply.select_programme'), [
            'mode' => 'UTME',
            'programme_id' => $this->ugProg->id,
        ]);

        $response->assertRedirect(route('applicant.payment.index'));

        $this->assertDatabaseHas('applicants', [
            'user_id' => $applicantUser->id,
            'application_mode' => 'UTME',
            'program_choice_1' => $this->ugProg->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'user_id' => $applicantUser->id,
            'type' => 'application_fee',
            'status' => 'pending',
            'amount' => 100000,
        ]);
    }

    public function test_unpaid_applicant_is_blocked_from_form_wizard()
    {
        $applicantUser = $this->createApplicantUser();

        // Select programme
        $this->actingAs($applicantUser)->post(route('applicant.apply.select_programme'), [
            'mode' => 'UTME',
            'programme_id' => $this->ugProg->id,
        ]);

        // Attempt to access form before payment
        $response = $this->actingAs($applicantUser)->get(route('applicant.apply.form'));
        $response->assertRedirect(route('applicant.payment.index'));
        $response->assertSessionHas('warning');
    }

    public function test_paid_applicant_can_access_form_wizard_and_submit()
    {
        $applicantUser = $this->createApplicantUser();

        $this->actingAs($applicantUser)->post(route('applicant.apply.select_programme'), [
            'mode' => 'UTME',
            'programme_id' => $this->ugProg->id,
        ]);

        // Mark invoice as paid
        Invoice::where('user_id', $applicantUser->id)->update(['status' => 'paid']);

        // Now applicant accesses form
        $formResponse = $this->actingAs($applicantUser)->get(route('applicant.apply.form'));
        $formResponse->assertOk();

        $state = \App\Models\State::create(['name' => 'Kogi']);
        $lga = \App\Models\Lga::create(['name' => 'Adavi', 'state_id' => $state->id]);

        // Submit application
        $submitResponse = $this->actingAs($applicantUser)->post(route('applicant.apply.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'dob' => '2000-01-01',
            'phone' => '08012345678',
            'address' => 'Test Address',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Jane Doe',
            'next_of_kin_phone' => '08098765432',
            'next_of_kin_relationship' => 'Mother',
            'mode' => 'UTME',
            'programme_id' => $this->ugProg->id,
            'jamb_registration_number' => '12345678AB',
            'jamb_score' => 250,
        ]);

        $submitResponse->assertRedirect(route('applicant.apply.show'));

        $this->assertDatabaseHas('applicants', [
            'user_id' => $applicantUser->id,
            'status' => 'submitted',
            'jamb_registration_number' => '12345678AB',
            'jamb_score' => 250,
        ]);
    }

    public function test_admin_cannot_admit_applicant_who_has_not_paid_fee()
    {
        $adminUser = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $adminUser->assignRole($adminRole);

        // Create applicant without paid invoice
        $applicantUser = $this->createApplicantUser();
        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'APP-UG-1234',
            'program_choice_1' => $this->ugProg->id,
            'application_mode' => 'UTME',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($adminUser)->put(route('admin.admissions.ug.update', $applicant), [
            'status' => 'admitted',
            'admitted_level' => '100',
            'admitted_programme_id' => $this->ugProg->id,
        ]);

        $response->assertSessionHas('error', 'Cannot admit applicant: Undergraduate application fee has not been paid.');
        $this->assertEquals('submitted', $applicant->fresh()->status);
    }

    public function test_admin_can_admit_applicant_once_fee_is_paid()
    {
        $adminUser = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $adminUser->assignRole($adminRole);

        $applicantUser = $this->createApplicantUser();
        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'APP-UG-1234',
            'program_choice_1' => $this->ugProg->id,
            'application_mode' => 'UTME',
            'status' => 'submitted',
        ]);

        // Pay application fee
        Invoice::create([
            'user_id' => $applicantUser->id,
            'reference' => 'APP-FEE-123',
            'type' => 'application_fee',
            'amount' => 100000,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($adminUser)->put(route('admin.admissions.ug.update', $applicant), [
            'status' => 'admitted',
            'admitted_level' => '100',
            'admitted_programme_id' => $this->ugProg->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('admitted', $applicant->fresh()->status);
    }

    public function test_admin_cannot_admit_pg_applicant_who_has_not_paid_fee()
    {
        $adminUser = User::factory()->create();
        $adminRole = Role::create(['name' => 'admin']);
        $adminUser->assignRole($adminRole);

        $applicantUser = $this->createApplicantUser();
        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'APP-PG-1234',
            'program_choice_1' => $this->pgProg->id,
            'application_mode' => 'PG',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($adminUser)->put(route('admin.admissions.pg.update', $applicant), [
            'status' => 'admitted',
            'admitted_level' => '800',
            'admitted_programme_id' => $this->pgProg->id,
        ]);

        $response->assertSessionHas('error', 'Cannot admit applicant: Postgraduate application & enrolment fee has not been paid.');
        $this->assertEquals('submitted', $applicant->fresh()->status);
    }

    public function test_applicant_can_incrementally_save_step_biodata()
    {
        $applicantUser = $this->createApplicantUser();

        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'APP-DRAFT-001',
            'program_choice_1' => $this->ugProg->id,
            'application_mode' => 'UTME',
            'status' => 'draft',
        ]);

        $state = \App\Models\State::create(['name' => 'Kano']);
        $lga = \App\Models\Lga::create(['name' => 'Dala', 'state_id' => $state->id]);

        $response = $this->actingAs($applicantUser)->postJson(route('applicant.apply.save_step'), [
            'step' => 'biodata',
            'first_name' => 'Ahmad',
            'last_name' => 'Aliyu',
            'phone' => '08031234567',
            'dob' => '2001-05-15',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'address' => '12 Zoo Road, Kano',
            'jamb_number' => '2026998877CD',
            'jamb_score' => 280,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $fresh = $applicant->fresh();
        $this->assertEquals('Ahmad', $fresh->first_name);
        $this->assertEquals('Aliyu', $fresh->last_name);
        $this->assertEquals('08031234567', $fresh->phone);
        $this->assertEquals('2001-05-15', $fresh->dob);
        $this->assertEquals($state->id, $fresh->state_id);
        $this->assertEquals($lga->id, $fresh->lga_id);
        $this->assertEquals('2026998877CD', $fresh->jamb_registration_number);
        $this->assertEquals(280, $fresh->jamb_score);
        $this->assertEquals('draft', $fresh->status); // Status must remain draft
    }

    public function test_applicant_can_save_step_with_documents_and_pg_qualifications()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $applicantUser = $this->createApplicantUser();

        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'APP-PG-DRAFT',
            'program_choice_1' => $this->pgProg->id,
            'application_mode' => 'PG',
            'status' => 'draft',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('passport.jpg');

        $response = $this->actingAs($applicantUser)->post(route('applicant.apply.save_step'), [
            'step' => 'academics',
            'qualifications' => [
                [
                    'qualification_type' => 'Other',
                    'other_qualification' => 'ANAN',
                    'institution_name' => 'Nigerian College of Accountancy',
                    'course_of_study' => 'Accountancy',
                    'class_of_degree' => 'Passed / Qualified',
                    'cgpa' => '4.20',
                    'graduation_year' => 2021,
                ],
            ],
            'referees' => [
                [
                    'name' => 'Prof. Sani',
                    'email' => 'sani@example.com',
                    'institution_or_organization' => 'Bayero University',
                    'relationship' => 'Supervisor',
                ],
            ],
            'passport_photo' => $file,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $fresh = $applicant->fresh();
        $this->assertCount(1, $fresh->qualifications);
        $this->assertEquals('ANAN', $fresh->qualifications->first()->qualification_type);
        $this->assertEquals('Nigerian College of Accountancy', $fresh->qualifications->first()->institution_name);

        $this->assertCount(1, $fresh->referees);
        $this->assertEquals('Prof. Sani', $fresh->referees->first()->name);

        $this->assertCount(1, $fresh->documents);
        $this->assertEquals('passport_photo', $fresh->documents->first()->type);
        $this->assertEquals('passport.jpg', $fresh->documents->first()->original_name);
    }

    public function test_applicant_cannot_save_step_if_already_submitted()
    {
        $applicantUser = $this->createApplicantUser();

        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'APP-SUBMITTED-01',
            'program_choice_1' => $this->ugProg->id,
            'application_mode' => 'UTME',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($applicantUser)->postJson(route('applicant.apply.save_step'), [
            'first_name' => 'Tampered Name',
        ]);

        $response->assertStatus(403);
        $this->assertNotEquals('Tampered Name', $applicant->fresh()->first_name);
    }
}
