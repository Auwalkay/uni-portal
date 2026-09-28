<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentGatewayInterface;
use Tests\TestCase;

class BulkRequeryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentUser;
    protected Session $session;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::create([
            'name' => 'Payment Admin',
            'email' => 'paymentadmin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        $this->session = Session::create([
            'name' => '2025/2026',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'is_current' => true,
        ]);

        $this->studentUser = User::create([
            'name' => 'Bulk Student',
            'email' => 'bulk.student@portal.com',
            'password' => Hash::make('password'),
        ]);

        Student::create([
            'user_id' => $this->studentUser->id,
            'matriculation_number' => 'MAT/BULK/001',
        ]);

        $this->invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'INV-BULK-001',
            'type' => 'school_fee',
            'amount' => 50000.00,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_admin_can_perform_bulk_requery_on_failed_payments()
    {
        \Illuminate\Support\Facades\Queue::fake();

        // 1. Create a failed payment
        $failedPayment = Payment::create([
            'user_id' => $this->studentUser->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 50000.00,
            'gateway' => 'seerbit',
            'transaction_id' => 'REF-BULK-FAIL-001',
            'gateway_reference' => 'REF-BULK-FAIL-001',
            'status' => 'failed',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.payments.bulk-requery'), []);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\BulkRequeryPaymentsJob::class);
    }

    public function test_bulk_requery_job_processes_failed_payments_in_background()
    {
        $failedPayment = Payment::create([
            'user_id' => $this->studentUser->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 50000.00,
            'gateway' => 'seerbit',
            'transaction_id' => 'REF-BULK-FAIL-002',
            'gateway_reference' => 'REF-BULK-FAIL-002',
            'status' => 'failed',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*encrypt/keys*' => \Illuminate\Support\Facades\Http::response([
                'status' => 'SUCCESS',
                'data' => [
                    'EncryptedSecKey' => ['encryptedKey' => 'token_123'],
                ]
            ], 200),
            '*payments/query/*' => \Illuminate\Support\Facades\Http::response([
                'status' => 'SUCCESS',
                'data' => [
                    'payments' => [
                        'paymentReference' => 'REF-BULK-FAIL-002',
                        'status' => '00',
                        'amount' => '50000.00',
                        'gatewayCode' => '00',
                        'message' => 'Approved',
                        'channel' => 'CARD',
                    ]
                ]
            ], 200),
        ]);

        $job = new \App\Jobs\BulkRequeryPaymentsJob([], $this->admin);
        $job->handle();

        $failedPayment->refresh();
        $this->assertEquals('success', $failedPayment->status);

        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
    }
}
