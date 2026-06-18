<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Scholarship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScholarshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_create_scholarship_with_coverages()
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.scholarships.store'), [
            'name' => 'Need Based Scholarship',
            'percentage' => 50,
            'covers_admin_charges' => true,
            'covers_hostel_fees' => true,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('scholarships', [
            'name' => 'Need Based Scholarship',
            'percentage' => 50,
            'covers_admin_charges' => true,
            'covers_hostel_fees' => true,
            'is_active' => true,
        ]);
    }

    public function test_student_on_100_percent_scholarship_can_auto_pay_zero_balance()
    {
        // 1. Create a student with 100% scholarship that covers admin/hostel
        $scholarship = Scholarship::create([
            'name' => '100% Scholarship',
            'percentage' => 100,
            'covers_admin_charges' => true,
            'covers_hostel_fees' => true,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Jane',
            'email' => 'jane@student.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = \App\Models\Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2026001',
            'scholarship_id' => $scholarship->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $session = \App\Models\Session::create([
            'name' => '2025/2026',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        // Generate school fee invoice
        $feeService = app(\App\Services\Finance\FeeService::class);
        
        // Let's mock a FeeConfiguration so that there's a base fee
        $feeType = \App\Models\FeeType::create(['name' => 'Tuition']);
        \App\Models\FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $feeType->id,
            'amount' => 50000,
            'level' => '100',
        ]);

        $invoice = $feeService->generateSchoolFeeInvoice($student, $session);

        $this->assertNotNull($invoice);
        $this->assertEquals(0, (float) $invoice->amount);

        // 2. Act as the student and attempt to pay the invoice
        $response = $this->actingAs($user)->post(route('student.payments.pay', $invoice->id), [
            'amount' => 0,
        ]);

        // 3. Assert redirection and that the invoice is paid and payment record exists
        $response->assertRedirect(route('student.payments.index'));
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, (float) $invoice->paid_amount);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 0,
            'status' => 'success',
            'gateway' => 'scholarship',
        ]);
    }

    public function test_student_on_fixed_amount_scholarship_fee_calculation()
    {
        $scholarship = Scholarship::create([
            'name' => 'Fixed 20k Scholarship',
            'type' => 'fixed',
            'amount' => 20000,
            'covers_admin_charges' => false,
            'covers_hostel_fees' => false,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'John Fixed',
            'email' => 'johnfixed@student.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = \App\Models\Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2026002',
            'scholarship_id' => $scholarship->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $session = \App\Models\Session::create([
            'name' => '2025/2026',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);
        
        $feeType = \App\Models\FeeType::create(['name' => 'Tuition']);
        \App\Models\FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $feeType->id,
            'amount' => 50000,
            'level' => '100',
        ]);

        \App\Models\SystemSetting::set('admin_charge_enabled', 'false');

        $invoice = $feeService->generateSchoolFeeInvoice($student, $session);

        $this->assertNotNull($invoice);
        $this->assertEquals(20000, (float) $invoice->amount);

        $discountItem = $invoice->items()->where('amount', '<', 0)->first();
        $this->assertNotNull($discountItem);
        $this->assertEquals(-30000, (float) $discountItem->amount);
        $this->assertStringContainsString('Fixed ₦20,000.00', $discountItem->description);
    }

    public function test_student_on_fixed_amount_scholarship_exceeding_base_fee()
    {
        $scholarship = Scholarship::create([
            'name' => 'Fixed 60k Scholarship',
            'type' => 'fixed',
            'amount' => 60000,
            'covers_admin_charges' => false,
            'covers_hostel_fees' => false,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'John High Fixed',
            'email' => 'johnhighfixed@student.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = \App\Models\Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2026003',
            'scholarship_id' => $scholarship->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $session = \App\Models\Session::create([
            'name' => '2025/2026',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);
        
        $feeType = \App\Models\FeeType::create(['name' => 'Tuition']);
        \App\Models\FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $feeType->id,
            'amount' => 50000,
            'level' => '100',
        ]);

        \App\Models\SystemSetting::set('admin_charge_enabled', 'false');

        $invoice = $feeService->generateSchoolFeeInvoice($student, $session);

        $this->assertNotNull($invoice);
        $this->assertEquals(50000, (float) $invoice->amount);

        $discountItem = $invoice->items()->where('amount', '<', 0)->first();
        $this->assertNull($discountItem);
    }

    public function test_scholarship_does_not_apply_to_one_time_fees()
    {
        // 1. Create student on 50% scholarship
        $scholarship = Scholarship::create([
            'name' => '50% Scholarship',
            'percentage' => 50,
            'covers_admin_charges' => false,
            'covers_hostel_fees' => false,
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Jane OneTime',
            'email' => 'janeonetime@student.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = \App\Models\Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2026004',
            'scholarship_id' => $scholarship->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $session = \App\Models\Session::create([
            'name' => '2025/2026',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);
        
        // Create Tuition (recurring) and Matriculation (one-time)
        $tuitionType = \App\Models\FeeType::create(['name' => 'Tuition Fee', 'is_one_time' => false]);
        $matricType = \App\Models\FeeType::create(['name' => 'Matriculation Fee', 'is_one_time' => true]);

        \App\Models\FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $tuitionType->id,
            'amount' => 100000,
            'level' => '100',
        ]);

        \App\Models\FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $matricType->id,
            'amount' => 20000,
            'level' => '100',
        ]);

        \App\Models\SystemSetting::set('admin_charge_enabled', 'false');

        $invoice = $feeService->generateSchoolFeeInvoice($student, $session);

        $this->assertNotNull($invoice);
        // Total should be: Tuition (100k) - 50% discount (50k) + One-Time Fee (20k) = 70,000 NGN
        $this->assertEquals(70000, (float) $invoice->amount);

        // Verify discount item is -50,000 NGN
        $discountItem = $invoice->items()->where('amount', '<', 0)->first();
        $this->assertNotNull($discountItem);
        $this->assertEquals(-50000, (float) $discountItem->amount);
    }
}

