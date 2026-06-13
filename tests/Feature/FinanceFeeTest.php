<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Session;
use App\Models\FeeType;
use App\Models\FeeConfiguration;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceFeeTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $studentUser;
    protected $student;
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        $this->studentUser = User::create([
            'name' => 'John Transfer',
            'email' => 'john@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->studentUser->assignRole('student');

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'matriculation_number' => 'STU-TR-001',
            'entry_mode' => 'Transfer',
            'current_level' => '200',
            'status' => 'active',
        ]);

        $this->session = Session::create([
            'name' => '2026/2027',
            'is_current' => true,
            'registration_enabled' => true,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);
    }

    public function test_one_time_fee_only_charged_once_in_school_fees()
    {
        // Create fee types: Tuition (recurring) and Transfer Fee (one-time)
        $tuitionType = FeeType::create([
            'name' => 'Tuition Fee',
            'slug' => 'tuition-fee',
            'is_one_time' => false,
        ]);

        $transferFeeType = FeeType::create([
            'name' => 'Transfer Student Fee',
            'slug' => 'transfer-student-fee',
            'is_one_time' => true,
        ]);

        // Configure rules for the session
        FeeConfiguration::create([
            'fee_type_id' => $tuitionType->id,
            'session_id' => $this->session->id,
            'amount' => 100000,
            'is_compulsory' => true,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $transferFeeType->id,
            'session_id' => $this->session->id,
            'amount' => 25000,
            'is_compulsory' => true,
            'entry_mode' => 'Transfer',
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);

        // 1st generation: should charge BOTH Tuition and Transfer Fee
        $invoice = $feeService->generateSchoolFeeInvoice($this->student, $this->session);

        $this->assertNotNull($invoice);
        $this->assertCount(2, $invoice->items()->whereNotNull('fee_type_id')->get());

        // Refresh/regenerate: should still have both since it's the SAME invoice being refreshed
        $refreshed = $feeService->refreshInvoiceIfUnpaid($invoice);
        $this->assertCount(2, $refreshed->items()->whereNotNull('fee_type_id')->get());

        // Simulate paying the first invoice (so we can generate a new one for a new session)
        $invoice->update(['status' => 'paid']);

        // Set up next session
        $nextSession = Session::create([
            'name' => '2027/2028',
            'is_current' => false,
            'registration_enabled' => true,
            'start_date' => now()->addYear(),
            'end_date' => now()->addYears(2),
        ]);

        // Configure next session rules
        FeeConfiguration::create([
            'fee_type_id' => $tuitionType->id,
            'session_id' => $nextSession->id,
            'amount' => 105000,
            'is_compulsory' => true,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $transferFeeType->id,
            'session_id' => $nextSession->id,
            'amount' => 25000,
            'is_compulsory' => true,
            'entry_mode' => 'Transfer',
        ]);

        // 2nd generation in next session: should ONLY charge Tuition, NOT the one-time Transfer Fee
        $nextInvoice = $feeService->generateSchoolFeeInvoice($this->student, $nextSession);
        
        $this->assertNotNull($nextInvoice);
        // Only 1 item because Transfer Student Fee is one-time and was already charged in the previous invoice
        $this->assertCount(1, $nextInvoice->items()->whereNotNull('fee_type_id')->get());
        $this->assertEquals($tuitionType->id, $nextInvoice->items()->whereNotNull('fee_type_id')->first()->fee_type_id);
    }

    public function test_optional_fees_are_not_automatically_included_in_school_fees()
    {
        $compulsoryType = FeeType::create([
            'name' => 'Compulsory Fee',
            'slug' => 'compulsory-fee',
            'is_one_time' => false,
        ]);

        $optionalType = FeeType::create([
            'name' => 'Gown Fee',
            'slug' => 'gown-fee',
            'is_one_time' => true,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $compulsoryType->id,
            'session_id' => $this->session->id,
            'amount' => 50000,
            'is_compulsory' => true,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $optionalType->id,
            'session_id' => $this->session->id,
            'amount' => 5000,
            'is_compulsory' => false, // OPTIONAL
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);
        $invoice = $feeService->generateSchoolFeeInvoice($this->student, $this->session);

        $this->assertNotNull($invoice);
        // Should only have Compulsory Fee, NOT optional Gown Fee
        $this->assertCount(1, $invoice->items()->whereNotNull('fee_type_id')->get());
        $this->assertEquals($compulsoryType->id, $invoice->items()->whereNotNull('fee_type_id')->first()->fee_type_id);
    }

    public function test_student_can_initiate_optional_fees_once()
    {
        $optionalType = FeeType::create([
            'name' => 'Matriculation Gown',
            'slug' => 'matriculation-gown',
            'is_one_time' => true,
        ]);

        $optionalConfig = FeeConfiguration::create([
            'fee_type_id' => $optionalType->id,
            'session_id' => $this->session->id,
            'amount' => 7500,
            'is_compulsory' => false, // OPTIONAL
        ]);

        // Access optional fees listing endpoint
        $response = $this->actingAs($this->studentUser)->get(route('student.payments.optional_fees'));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $this->assertEquals($optionalConfig->id, $response->json()[0]['id']);

        // Initiate the optional fee
        $response = $this->actingAs($this->studentUser)->post(route('student.payments.initiate_optional', $optionalConfig->id));
        $response->assertRedirect();
        
        // Assert invoice of type 'other_fee' is created
        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->studentUser->id,
            'type' => 'other_fee',
            'amount' => 7500,
        ]);

        // Try to list optional fees again -> should be empty since it is one-time and invoice exists
        $response = $this->actingAs($this->studentUser)->get(route('student.payments.optional_fees'));
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_admission_letter_saved_to_storage()
    {
        Storage::fake('local');

        $applicant = Applicant::create([
            'user_id' => $this->studentUser->id,
            'jamb_registration_number' => 'JAMB123456',
            'status' => 'admitted',
        ]);

        // First download -> should create the file in storage
        $response = $this->actingAs($this->studentUser)->get(route('student.admission_letter.download'));
        $response->assertStatus(200);
        
        Storage::disk('local')->assertExists("admission_letters/{$this->studentUser->id}.pdf");

        // Set test content to ensure it uses the cached file on subsequent hits
        Storage::disk('local')->put("admission_letters/{$this->studentUser->id}.pdf", "CACHED_PDF_CONTENT");

        // Second download -> should return the cached file
        $response2 = $this->actingAs($this->studentUser)->get(route('student.admission_letter.download'));
        $response2->assertStatus(200);
        $this->assertEquals("CACHED_PDF_CONTENT", $response2->streamedContent());
    }

    public function test_other_fees_do_not_support_split_payments()
    {
        $optionalType = FeeType::create([
            'name' => 'Matriculation Gown',
            'slug' => 'matriculation-gown',
            'is_one_time' => true,
        ]);

        $optionalConfig = FeeConfiguration::create([
            'fee_type_id' => $optionalType->id,
            'session_id' => $this->session->id,
            'amount' => 10000,
            'is_compulsory' => false,
        ]);

        // Initiate the optional fee to create the invoice
        $this->actingAs($this->studentUser)->post(route('student.payments.initiate_optional', $optionalConfig->id));

        $invoice = Invoice::where('user_id', $this->studentUser->id)->where('type', 'other_fee')->first();
        $this->assertNotNull($invoice);

        // Try to pay split amount (e.g. 5,000 instead of 10,000) -> should fail validation/redirect back with error
        $response = $this->actingAs($this->studentUser)
            ->from(route('student.payments.index'))
            ->post(route('student.payments.pay', $invoice->id), [
                'amount' => 5000,
            ]);

        $response->assertRedirect(route('student.payments.index'));
        $response->assertSessionHas('error', 'Split payments are not supported for this type of fee. The full remaining balance of 10,000.00 NGN must be paid.');
    }

    public function test_cache_clearing_on_fee_types_and_configurations_changes()
    {
        Storage::fake('local');

        // Put some fake cached admission letters
        Storage::disk('local')->put("admission_letters/1.pdf", "FAKE_PDF");
        Storage::disk('local')->put("admission_letters/2.pdf", "FAKE_PDF");

        Storage::disk('local')->assertExists("admission_letters/1.pdf");
        Storage::disk('local')->assertExists("admission_letters/2.pdf");

        // Call storeFeeType to see if it triggers deletion
        $response = $this->actingAs($this->admin)->post(route('admin.finance.fee_types.store'), [
            'name' => 'Cache Test Fee Type',
            'description' => 'Will delete cache',
            'is_one_time' => false,
        ]);

        $response->assertRedirect();
        Storage::disk('local')->assertMissing("admission_letters/1.pdf");
        Storage::disk('local')->assertMissing("admission_letters/2.pdf");
    }
}

