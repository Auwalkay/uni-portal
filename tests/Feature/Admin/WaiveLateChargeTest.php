<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Session;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Finance\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class WaiveLateChargeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Session $academicSession;
    protected Session $previousSession;
    protected User $studentUser;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        SystemSetting::set('late_fee_enabled', '1');
        SystemSetting::set('late_fee_amount', '10000');

        // Academic session with overdue deadline
        $this->academicSession = Session::create([
            'name' => '2026/2027 Academic Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'type' => 'regular',
            'is_current' => true,
            'late_payment_deadline' => now()->subDays(5),
            'late_fee_amount' => 10000,
        ]);

        $this->previousSession = Session::create([
            'name' => '2025/2026 Academic Session',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'type' => 'regular',
        ]);

        // Returning student (admitted in previous session)
        $this->studentUser = User::create([
            'name' => 'Returning Student',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'admitted_session_id' => $this->previousSession->id,
            'current_level' => 200,
            'status' => 'active',
        ]);

        // Admin with waive_late_charges permission
        $this->adminUser = User::create([
            'name' => 'Finance Admin',
            'email' => 'bursar@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole('bursar');

        // Unauthorized user (lecturer role)
        $this->unauthorizedUser = User::create([
            'name' => 'Regular Lecturer',
            'email' => 'lecturer@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->unauthorizedUser->assignRole('lecturer');
    }

    private function createOverdueInvoice(float $baseAmount = 150000, float $fineAmount = 10000): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->academicSession->id,
            'type' => 'school_fee',
            'reference' => 'SCH-TEST-' . strtoupper(uniqid()),
            'invoice_number' => 'INV-TEST-' . strtoupper(uniqid()),
            'amount' => $baseAmount + $fineAmount,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->subDays(3),
            'late_fine_applied' => true,
            'late_fine_waived' => false,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Tuition Fee (200 Level)',
            'amount' => $baseAmount,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Late Payment Fine (2026/2027 Academic Session)',
            'amount' => $fineAmount,
        ]);

        return $invoice;
    }

    public function test_user_without_permission_cannot_waive_late_charge()
    {
        $invoice = $this->createOverdueInvoice();

        $response = $this->actingAs($this->unauthorizedUser)
            ->post(route('admin.invoices.waive-late-charge', $invoice->id), [
                'reason' => 'Unauthorized attempt',
            ]);

        $response->assertStatus(403);

        $invoice->refresh();
        $this->assertTrue($invoice->late_fine_applied);
        $this->assertFalse($invoice->late_fine_waived);
    }

    public function test_authorized_admin_can_successfully_waive_late_charge()
    {
        $invoice = $this->createOverdueInvoice(150000, 10000);
        $this->assertEquals(160000, (float) $invoice->amount);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.invoices.waive-late-charge', $invoice->id), [
                'reason' => 'Approved VC concession on medical grounds',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice->refresh();

        // Amount reduced by ₦10,000 late fine
        $this->assertEquals(150000, (float) $invoice->amount);
        $this->assertFalse($invoice->late_fine_applied);
        $this->assertTrue($invoice->late_fine_waived);
        $this->assertEquals('Approved VC concession on medical grounds', $invoice->late_fine_waived_reason);
        $this->assertEquals($this->adminUser->id, $invoice->late_fine_waived_by);
        $this->assertNotNull($invoice->late_fine_waived_at);

        // Assert late fine line item was deleted
        $this->assertDatabaseMissing('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Late Payment Fine (2026/2027 Academic Session)',
        ]);

        // Base tuition item still exists
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Tuition Fee (200 Level)',
            'amount' => 150000,
        ]);

        // Activity log recorded
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $invoice->id,
            'causer_id' => $this->adminUser->id,
            'description' => 'Waived late payment fine of ₦10,000.00',
        ]);
    }

    public function test_cannot_waive_already_waived_invoice()
    {
        $invoice = $this->createOverdueInvoice();

        // First waiver
        $this->actingAs($this->adminUser)
            ->post(route('admin.invoices.waive-late-charge', $invoice->id), [
                'reason' => 'First waiver',
            ])
            ->assertSessionHas('success');

        // Second waiver attempt
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.invoices.waive-late-charge', $invoice->id), [
                'reason' => 'Duplicate attempt',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Late payment fine has already been waived for this invoice.');
    }

    public function test_fee_service_refresh_does_not_reapply_waived_fine()
    {
        $invoice = $this->createOverdueInvoice(150000, 10000);

        // Waive the fine
        $this->actingAs($this->adminUser)
            ->post(route('admin.invoices.waive-late-charge', $invoice->id), [
                'reason' => 'Medical fee waiver',
            ]);

        $invoice->refresh();
        $this->assertTrue($invoice->late_fine_waived);
        $this->assertEquals(150000, (float) $invoice->amount);

        // Simulate student viewing invoice (triggers FeeService::refreshInvoiceIfUnpaid)
        $feeService = app(FeeService::class);
        $refreshed = $feeService->refreshInvoiceIfUnpaid($invoice);

        $refreshed->refresh();
        $this->assertFalse($refreshed->late_fine_applied);
        $this->assertTrue($refreshed->late_fine_waived);
        $this->assertEquals(150000, (float) $refreshed->amount);

        // Ensure late fine item was not recreated
        $hasLateFine = $refreshed->items()->where('description', 'LIKE', '%Late%')->exists();
        $this->assertFalse($hasLateFine);
    }

    public function test_cron_command_does_not_reapply_waived_fine()
    {
        $invoice = $this->createOverdueInvoice(150000, 10000);

        // Waive the fine
        $this->actingAs($this->adminUser)
            ->post(route('admin.invoices.waive-late-charge', $invoice->id), [
                'reason' => 'Formal concession',
            ]);

        $invoice->refresh();
        $this->assertTrue($invoice->late_fine_waived);

        // Run the cron command
        $this->artisan('fees:apply-late-payment-fines')
            ->assertExitCode(0);

        // Invoice should remain untouched
        $invoice->refresh();
        $this->assertFalse($invoice->late_fine_applied);
        $this->assertTrue($invoice->late_fine_waived);
        $this->assertEquals(150000, (float) $invoice->amount);
    }
}
