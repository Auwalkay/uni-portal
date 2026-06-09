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
}

