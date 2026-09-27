<?php

use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelBooking;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentHandler;
use App\Services\SeerbitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('seerbit service uses global credentials by default', function () {
    config([
        'services.seerbit.secret_key' => 'seerbit_secret_key_123',
        'services.seerbit.public_key' => 'seerbit_public_key_456',
    ]);

    $service = new SeerbitService();
    expect($service->getSecretKey())->toBe('seerbit_secret_key_123');
    expect($service->getPublicKey())->toBe('seerbit_public_key_456');
});

test('payment gateway factory resolves seerbit service when specified', function () {
    config(['services.seerbit.secret_key' => 'seerbit_secret_123']);

    $user = User::factory()->create();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-SEERBIT-001',
        'type' => 'school_fee',
        'amount' => 150000,
        'status' => 'pending',
    ]);

    [$gatewayName, $service] = PaymentGatewayFactory::resolveWithGatewayName($invoice, 'seerbit');

    expect($gatewayName)->toBe('seerbit');
    expect($service)->toBeInstanceOf(SeerbitService::class);
});

test('seerbit service initializes payment transaction successfully', function () {
    config([
        'services.seerbit.base_url' => 'https://merchants.seerbitapi.com/api/v2',
        'services.seerbit.secret_key' => 'test_sec_key',
        'services.seerbit.public_key' => 'test_pub_key',
    ]);

    Http::fake([
        '*encrypt/keys*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'EncryptedSecKey' => ['encryptedKey' => 'mocked_bearer_token_123'],
                'EncryptedKeys' => ['token' => 'mocked_bearer_token_123']
            ]
        ], 200),
        '*payments*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'payments' => [
                    'redirectLink' => 'https://checkout.seerbit.com/pay/mocked_checkout',
                    'redirectUrl' => 'https://checkout.seerbit.com/pay/mocked_checkout',
                    'paymentReference' => 'SEERBIT-REF-1001',
                ]
            ]
        ], 200),
    ]);

    $service = new SeerbitService();
    $result = $service->initializeTransaction(
        'student@university.edu.ng',
        50000.00,
        'SEERBIT-REF-1001',
        'https://university.edu.ng/callback',
        ['payment_type' => 'school_fee']
    );

    expect($result)->not->toBeNull();
    expect($result['authorization_url'])->toBe('https://checkout.seerbit.com/pay/mocked_checkout');
    expect($result['reference'])->toBe('SEERBIT-REF-1001');
});

test('seerbit service verifies transaction status successfully', function () {
    config([
        'services.seerbit.base_url' => 'https://merchants.seerbitapi.com/api/v2',
        'services.seerbit.secret_key' => 'test_sec_key',
        'services.seerbit.public_key' => 'test_pub_key',
    ]);

    Http::fake([
        '*encrypt/keys*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'EncryptedSecKey' => ['encryptedKey' => 'mocked_bearer_token_123']
            ]
        ], 200),
        '*payments/query/SEERBIT-REF-1001*' => Http::response([
            'code' => '00',
            'status' => 'SUCCESS',
            'data' => [
                'payments' => [
                    'paymentReference' => 'SEERBIT-REF-1001',
                    'amount' => 50000.00,
                    'status' => 'SUCCESS',
                    'paymentType' => 'card',
                    'gatewayCode' => '00',
                    'gatewayMessage' => 'Successful',
                ]
            ]
        ], 200),
    ]);

    $service = new SeerbitService();
    $verification = $service->verifyTransaction('SEERBIT-REF-1001');

    expect($verification)->not->toBeNull();
    expect($verification['status'])->toBe('success');
    expect($verification['amount'])->toBe(50000.00);
    expect($verification['channel'])->toBe('card');
});

test('seerbit service marks cancelled or failed transaction as failed', function () {
    config([
        'services.seerbit.base_url' => 'https://seerbitapi.com/api/v2',
        'services.seerbit.secret_key' => 'test_sec_key',
        'services.seerbit.public_key' => 'test_pub_key',
    ]);

    Http::fake([
        '*encrypt/keys*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'EncryptedSecKey' => ['encryptedKey' => 'mocked_bearer_token_123']
            ]
        ], 200),
        '*payments/query/SEERBIT-REF-CANCELLED' => Http::response([
            'status' => 'SUCCESS',
            'code' => '00',
            'data' => [
                'payments' => [
                    'paymentReference' => 'SEERBIT-REF-CANCELLED',
                    'amount' => 50000.00,
                    'status' => 'CANCELLED',
                    'code' => 'S09',
                    'gatewayMessage' => 'User cancelled payment',
                ]
            ]
        ], 200),
    ]);

    $service = new SeerbitService();
    $verification = $service->verifyTransaction('SEERBIT-REF-CANCELLED');

    expect($verification)->not->toBeNull();
    expect($verification['status'])->toBe('failed');
    expect($verification['gateway_response'])->toBe('User cancelled payment');
});

test('seerbit webhook processes valid payment signature and completes payment', function () {
    config([
        'services.seerbit.secret_key' => 'seerbit_webhook_secret',
    ]);

    $user = User::factory()->create();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-SCH-5050',
        'type' => 'school_fee',
        'amount' => 75000,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'transaction_id' => 'MIUPAY-SEERBIT-101',
        'gateway' => 'seerbit',
        'gateway_reference' => 'SEERBIT-REF-5050',
        'amount' => 75000,
        'status' => 'pending',
    ]);

    $webhookPayload = json_encode([
        'code' => '00',
        'status' => 'SUCCESS',
        'notificationType' => 'SUCCESSFUL_TRANSACTION',
        'data' => [
            'payments' => [
                'paymentReference' => 'SEERBIT-REF-5050',
                'amount' => 75000,
                'status' => 'SUCCESS',
                'paymentType' => 'card',
            ]
        ]
    ]);

    $signature = hash_hmac('sha512', $webhookPayload, 'seerbit_webhook_secret');

    $response = $this->postJson('/webhooks/seerbit', json_decode($webhookPayload, true), [
        'X-Seerbit-Signature' => $signature,
    ]);

    $response->assertStatus(200);

    expect($payment->fresh()->status)->toBe('success');
    expect($invoice->fresh()->status)->toBe('paid');
});

test('hostel gateway configuration resolves custom seerbit keys properly', function () {
    $hostel = Hostel::create([
        'name' => 'Seerbit Dedicated Hall',
        'gender_type' => 'mixed',
        'payment_gateway' => 'seerbit',
        'seerbit_secret_key' => 'custom_seerbit_sk_999',
        'seerbit_public_key' => 'custom_seerbit_pk_888',
    ]);

    expect($hostel->getSecretKeyForGateway('seerbit'))->toBe('custom_seerbit_sk_999');
    expect($hostel->getPublicKeyForGateway('seerbit'))->toBe('custom_seerbit_pk_888');
    expect($hostel->hasCustomGatewayConfig())->toBeTrue();
});
