<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createStudentUser() {
    Permission::findOrCreate('access_student_portal');

    $user = User::create([
        'name' => 'Student User ' . uniqid(),
        'email' => 'student_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);

    $user->givePermissionTo('access_student_portal');

    return $user;
}

test('student can requery pending payment by payment ID', function () {
    $user = createStudentUser();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-REQUERY-001',
        'type' => 'school_fee',
        'amount' => 50000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'user_id' => $user->id,
        'invoice_id' => $invoice->id,
        'transaction_id' => 'PAY-REQUERY-REF-001',
        'gateway' => 'seerbit',
        'gateway_reference' => 'PAY-REQUERY-REF-001',
        'amount' => 50000,
        'status' => 'pending',
    ]);

    Http::fake([
        '*encrypt/keys*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'EncryptedSecKey' => ['encryptedKey' => 'token_123'],
            ]
        ], 200),
        '*payments/query/*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'payments' => [
                    'paymentReference' => 'PAY-REQUERY-REF-001',
                    'status' => '00',
                    'amount' => '50000.00',
                    'gatewayCode' => '00',
                    'message' => 'Approved',
                    'channel' => 'CARD',
                ]
            ]
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->post(route('student.payments.requery', $payment->id));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $payment->refresh();
    $invoice->refresh();

    expect($payment->status)->toBe('success');
    expect($invoice->status)->toBe('paid');
    expect((float)$invoice->paid_amount)->toEqual(50000.00);
});

test('student can requery payment by reference code', function () {
    $user = createStudentUser();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-REQUERY-002',
        'type' => 'school_fee',
        'amount' => 75000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'user_id' => $user->id,
        'invoice_id' => $invoice->id,
        'transaction_id' => 'PAY-REF-SEARCH-123',
        'gateway' => 'seerbit',
        'gateway_reference' => 'PAY-REF-SEARCH-123',
        'amount' => 75000,
        'status' => 'pending',
    ]);

    Http::fake([
        '*encrypt/keys*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'EncryptedSecKey' => ['encryptedKey' => 'token_123'],
            ]
        ], 200),
        '*payments/query/*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'payments' => [
                    'paymentReference' => 'PAY-REF-SEARCH-123',
                    'status' => '00',
                    'amount' => '75000.00',
                    'gatewayCode' => '00',
                    'message' => 'Approved',
                    'channel' => 'TRANSFER',
                ]
            ]
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->post(route('student.payments.requery_reference'), [
            'reference' => 'PAY-REF-SEARCH-123',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $payment->refresh();
    $invoice->refresh();

    expect($payment->status)->toBe('success');
    expect($invoice->status)->toBe('paid');
});

test('student cannot requery another student payment', function () {
    $user1 = createStudentUser();
    $user2 = createStudentUser();

    $invoice = Invoice::create([
        'user_id' => $user1->id,
        'reference' => 'INV-REQUERY-003',
        'type' => 'school_fee',
        'amount' => 50000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'user_id' => $user1->id,
        'invoice_id' => $invoice->id,
        'transaction_id' => 'PAY-USER1-REF',
        'gateway' => 'seerbit',
        'gateway_reference' => 'PAY-USER1-REF',
        'amount' => 50000,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user2)
        ->post(route('student.payments.requery', $payment->id));

    $response->assertStatus(403);
});

test('student can view invoice details page with payment attempts history', function () {
    $user = createStudentUser();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-SHOW-PAGE-001',
        'type' => 'school_fee',
        'amount' => 100000,
        'paid_amount' => 50000,
        'status' => 'partial',
    ]);

    Payment::create([
        'user_id' => $user->id,
        'invoice_id' => $invoice->id,
        'transaction_id' => 'PAY-SUCCESS-ATTEMPT',
        'gateway' => 'seerbit',
        'gateway_reference' => 'PAY-SUCCESS-ATTEMPT',
        'amount' => 50000,
        'status' => 'success',
    ]);

    Payment::create([
        'user_id' => $user->id,
        'invoice_id' => $invoice->id,
        'transaction_id' => 'PAY-FAILED-ATTEMPT',
        'gateway' => 'seerbit',
        'gateway_reference' => 'PAY-FAILED-ATTEMPT',
        'amount' => 50000,
        'status' => 'failed',
    ]);

    $response = $this->actingAs($user)
        ->get(route('student.payments.invoice.show', $invoice->id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Student/Finance/ShowInvoice')
        ->has('invoice')
        ->where('invoice.id', $invoice->id)
        ->has('invoice.payments', 2)
    );
});

test('late fine is automatically waived if payment was initiated before late deadline even if requery happens after deadline', function () {
    $user = createStudentUser();
    $session = \App\Models\Session::create([
        'name' => '2025/2026 Test Session',
        'is_current' => true,
        'school_fee_payment_enabled' => true,
        'start_date' => now()->subMonths(1),
        'end_date' => now()->addMonths(5),
        'late_payment_deadline' => now()->subHours(2), // Deadline was 2 hours ago
        'late_fee_amount' => 15000,
    ]);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'session_id' => $session->id,
        'reference' => 'INV-LATE-FINE-PROTECTED',
        'type' => 'school_fee',
        'amount' => 115000, // Includes 15000 late fine
        'paid_amount' => 0,
        'status' => 'pending',
        'late_fine_applied' => true,
    ]);

    \App\Models\InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Tuition Fee',
        'amount' => 100000,
    ]);

    \App\Models\InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Late Payment Fine (2025/2026 Test Session)',
        'amount' => 15000,
    ]);

    // Payment was initiated 5 hours ago (BEFORE the deadline 2 hours ago)
    $payment = Payment::create([
        'user_id' => $user->id,
        'invoice_id' => $invoice->id,
        'transaction_id' => 'PAY-PRE-DEADLINE-INITIATED',
        'gateway' => 'seerbit',
        'gateway_reference' => 'PAY-PRE-DEADLINE-INITIATED',
        'amount' => 100000,
        'status' => 'pending',
        'created_at' => now()->subHours(5),
    ]);

    Http::fake([
        '*encrypt/keys*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'EncryptedSecKey' => ['encryptedKey' => 'token_123'],
            ]
        ], 200),
        '*payments/query/*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'payments' => [
                    'paymentReference' => 'PAY-PRE-DEADLINE-INITIATED',
                    'status' => '00',
                    'amount' => '100000.00',
                    'gatewayCode' => '00',
                    'message' => 'Approved',
                    'channel' => 'CARD',
                    'created_at' => now()->subHours(5)->toIso8601String(),
                ]
            ]
        ], 200),
    ]);

    // Requery happens NOW (after deadline)
    $response = $this->actingAs($user)
        ->post(route('student.payments.requery', $payment->id));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $payment->refresh();
    $invoice->refresh();

    expect($payment->status)->toBe('success');
    expect((float)$invoice->amount)->toEqual(100000.00); // Late fine 15000 was stripped
    expect($invoice->status)->toBe('paid');
    expect((float)$invoice->paid_amount)->toEqual(100000.00);
});
