<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\Session;
use App\Models\User;
use App\Services\AcademicCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ApplicantSessionAndProgrammesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AcademicCacheService::clearAll();
    }

    private function createApplicantWithInvoice(): array
    {
        $session = Session::create([
            'name' => '2026/2026',
            'is_current' => true,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'registration_enabled' => true,
            'applications_enabled' => true,
            'admissions_enabled' => true,
        ]);

        Permission::findOrCreate('access_applicant_portal');

        $user = User::factory()->create();
        $user->givePermissionTo('access_applicant_portal');

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'reference' => 'APP-TEST-' . uniqid(),
            'type' => 'application_fee',
            'amount' => 100000,
            'status' => 'pending',
            'session_id' => $session->id,
        ]);

        return [$user, $session, $invoice];
    }

    public function test_applicant_payment_page_loads_with_active_session()
    {
        [$user, $session, $invoice] = $this->createApplicantWithInvoice();

        $response = $this->actingAs($user)->get(route('applicant.payment.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Applicant/Payment/Index')
            ->has('invoice')
            ->where('invoice.reference', $invoice->reference)
            ->where('invoice.session.name', '2026/2026')
            ->where('currentSession.name', '2026/2026')
        );
    }

    public function test_applicant_start_page_displays_active_session_and_only_active_programmes()
    {
        $session = Session::create([
            'name' => '2026/2026',
            'is_current' => true,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'registration_enabled' => true,
            'applications_enabled' => true,
            'admissions_enabled' => true,
        ]);

        Permission::findOrCreate('access_applicant_portal');
        $user = User::factory()->create();
        $user->givePermissionTo('access_applicant_portal');

        $faculty = Faculty::create(['name' => 'Faculty of Engineering', 'code' => 'ENG']);
        $department = Department::create(['name' => 'Computer Engineering', 'code' => 'CPE', 'faculty_id' => $faculty->id]);

        $activeProg = Programme::create([
            'name' => 'B.Eng Computer Engineering',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $inactiveProg = Programme::create([
            'name' => 'B.Eng Telecommunications',
            'department_id' => $department->id,
            'is_active' => false,
        ]);

        AcademicCacheService::clearAll();

        $response = $this->actingAs($user)->get(route('applicant.apply.start'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Applicant/Application/Start')
            ->where('currentSession.name', '2026/2026')
            ->has('faculties', 1)
            ->where('faculties.0.departments.0.programmes.0.name', 'B.Eng Computer Engineering')
            ->has('faculties.0.departments.0.programmes', 1)
        );
    }

    public function test_applicant_cannot_submit_application_for_an_inactive_programme()
    {
        $session = Session::create([
            'name' => '2026/2026',
            'is_current' => true,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'registration_enabled' => true,
            'applications_enabled' => true,
            'admissions_enabled' => true,
        ]);

        Permission::findOrCreate('access_applicant_portal');
        $user = User::factory()->create();
        $user->givePermissionTo('access_applicant_portal');

        $faculty = Faculty::create(['name' => 'Faculty of Sciences', 'code' => 'SCI']);
        $department = Department::create(['name' => 'Physics Dept', 'code' => 'PHY', 'faculty_id' => $faculty->id]);

        $inactiveProg = Programme::create([
            'name' => 'B.Sc Nuclear Physics',
            'department_id' => $department->id,
            'is_active' => false,
        ]);

        $state = \App\Models\State::create(['name' => 'Kano']);
        $lga = \App\Models\Lga::create(['name' => 'Nassarawa', 'state_id' => $state->id]);

        $response = $this->actingAs($user)->post(route('applicant.apply.store'), [
            'first_name' => 'Aliyu',
            'last_name' => 'Bello',
            'dob' => '2002-05-10',
            'phone' => '08099887766',
            'jamb_score' => 260,
            'programme_id' => $inactiveProg->id,
            'mode' => 'UTME',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'next_of_kin_name' => 'Father Bello',
            'next_of_kin_phone' => '08011223344',
            'next_of_kin_relationship' => 'Father',
        ]);

        $response->assertSessionHasErrors(['programme_id']);
    }

    public function test_applicant_dashboard_shows_pending_payment_when_unpaid()
    {
        [$user, $session, $invoice] = $this->createApplicantWithInvoice();

        Applicant::create([
            'user_id' => $user->id,
            'jamb_registration_number' => 'JAMB-TEST-001',
            'application_mode' => 'UTME',
            'status' => 'pending_payment',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '2000-01-01',
            'phone' => '08012345678',
        ]);

        $response = $this->actingAs($user)->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Applicant/Dashboard')
            ->where('applicant.status', 'pending_payment')
            ->where('hasPaidApplicationFee', false)
            ->where('applicationInvoice.reference', $invoice->reference)
        );
    }

    public function test_applicant_can_access_payment_page_after_submission_even_if_invoice_was_missing()
    {
        $session = Session::create([
            'name' => '2026/2026',
            'is_current' => true,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'registration_enabled' => true,
            'applications_enabled' => true,
            'admissions_enabled' => true,
        ]);

        Permission::findOrCreate('access_applicant_portal');
        $user = User::factory()->create();
        $user->givePermissionTo('access_applicant_portal');

        // Applicant exists with pending_payment but no invoice created yet
        Applicant::create([
            'user_id' => $user->id,
            'jamb_registration_number' => 'JAMB-TEST-002',
            'application_mode' => 'UTME',
            'status' => 'pending_payment',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '2001-02-02',
            'phone' => '08098765432',
        ]);

        $response = $this->actingAs($user)->get(route('applicant.payment.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Applicant/Payment/Index')
            ->has('invoice')
            ->where('invoice.session.name', '2026/2026')
        );

        // Verify invoice was created automatically
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'type' => 'application_fee',
            'status' => 'pending',
        ]);
    }

    public function test_applicant_show_preview_page_indicates_unpaid_payment_state()
    {
        [$user, $session, $invoice] = $this->createApplicantWithInvoice();

        Applicant::create([
            'user_id' => $user->id,
            'jamb_registration_number' => 'JAMB-TEST-003',
            'application_mode' => 'UTME',
            'status' => 'pending_payment',
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'dob' => '2000-03-03',
            'phone' => '08055555555',
        ]);

        $response = $this->actingAs($user)->get(route('applicant.apply.show'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Applicant/Application/Show')
            ->where('applicant.status', 'pending_payment')
            ->where('hasPaidApplicationFee', false)
            ->where('invoice.reference', $invoice->reference)
        );
    }

    public function test_applicant_with_paid_application_fee_is_redirected_to_preview_from_payment_index()
    {
        [$user, $session, $invoice] = $this->createApplicantWithInvoice();
        $invoice->update(['status' => 'paid']);

        Applicant::create([
            'user_id' => $user->id,
            'jamb_registration_number' => 'JAMB-TEST-004',
            'application_number' => 'APP2026001',
            'application_mode' => 'UTME',
            'status' => 'submitted',
            'first_name' => 'Mark',
            'last_name' => 'Taylor',
            'dob' => '2000-04-04',
            'phone' => '08044444444',
        ]);

        $response = $this->actingAs($user)->get(route('applicant.payment.index'));

        $response->assertRedirect(route('applicant.apply.show'));
    }
}
