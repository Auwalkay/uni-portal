<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Session;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LatePaymentFineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_can_toggle_late_fee_settings_in_controller()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        // Toggle settings
        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'key' => 'late_fee_enabled',
            'value' => '1',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('1', SystemSetting::get('late_fee_enabled'));

        // Toggle fine amount
        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'key' => 'late_fee_amount',
            'value' => '15000',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('15000', SystemSetting::get('late_fee_amount'));
    }

    public function test_apply_late_payment_fines_command_applies_fine_to_overdue_invoices()
    {
        // 1. Setup system settings
        SystemSetting::set('late_fee_enabled', '1');
        SystemSetting::set('late_fee_amount', '10000');

        // 2. Create overdue session
        $session = Session::create([
            'name' => '2026/2027 Academic Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'type' => 'regular',
            'is_current' => true,
            'late_payment_deadline' => now()->subDays(2), // Overdue
        ]);

        // 3. Create student user
        $studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);

        // 4. Create pending school fee invoice
        $invoice = Invoice::create([
            'user_id' => $studentUser->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'INV-TEST-LATE',
            'invoice_number' => 'INV-NUM-LATE',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->subDays(1),
            'late_fine_applied' => false,
        ]);

        // 5. Run the artisan command
        $this->artisan('fees:apply-late-payment-fines')
            ->expectsOutput("Found 1 overdue invoice(s). Appending late payment fines...")
            ->expectsOutput("Successfully applied late payment fines to 1 invoice(s).")
            ->assertExitCode(0);

        // 6. Assert invoice updated
        $invoice->refresh();
        $this->assertEquals(110000, (float)$invoice->amount);
        $this->assertTrue($invoice->late_fine_applied);

        // Assert InvoiceItem created
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Late Payment Fine (2026/2027 Academic Session)',
            'amount' => 10000,
        ]);
    }

    public function test_student_payment_is_blocked_if_school_fees_disabled_for_session()
    {
        $session = Session::create([
            'name' => '2026/2027 Academic Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'type' => 'regular',
            'is_current' => true,
            'school_fee_payment_enabled' => false, // DISABLED
        ]);

        $studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);

        $invoice = Invoice::create([
            'user_id' => $studentUser->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'INV-TEST-DISABLE',
            'invoice_number' => 'INV-NUM-DISABLE',
            'amount' => 100000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(10),
            'late_fine_applied' => false,
        ]);

        // Try to pay
        $response = $this->actingAs($studentUser)->post(route('student.payments.pay', $invoice->id), [
            'amount' => 50000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'School fee payments are currently disabled for the 2026/2027 Academic Session.');
    }
}
