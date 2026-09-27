<?php

use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelBooking;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use App\Models\Invoice;
use App\Services\PaystackService;
use App\Services\SquadcoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('squadco service uses global secret key by default', function () {
    config(['services.squadco.secret_key' => 'global_squadco_key_123']);

    $service = new SquadcoService();
    expect($service->getSecretKey())->toBe('global_squadco_key_123');
});

test('squadco service resolves custom secret key for specific hostel invoice', function () {
    config(['services.squadco.secret_key' => 'global_squadco_key_123']);

    $hostel = Hostel::create([
        'name' => 'Hostel A',
        'gender_type' => 'mixed',
        'description' => 'Test Hostel A',
        'payment_gateway' => 'squadco',
        'squadco_secret_key' => 'hostel_a_custom_secret_key',
        'is_visible' => true,
    ]);

    $block = HostelBlock::create([
        'hostel_id' => $hostel->id,
        'name' => 'Block A',
        'is_visible' => true,
    ]);

    $floor = HostelFloor::create([
        'hostel_block_id' => $block->id,
        'name' => 'First Floor',
        'is_visible' => true,
    ]);

    $room = HostelRoom::create([
        'hostel_floor_id' => $floor->id,
        'room_number' => '101',
        'capacity' => 4,
        'is_visible' => true,
    ]);

    $user1 = \App\Models\User::factory()->create();
    $student1 = \App\Models\Student::create([
        'user_id' => $user1->id,
        'matriculation_number' => 'MAT-' . rand(1000, 9999),
        'status' => 'active',
    ]);
    $session1 = \App\Models\Session::create([
        'name' => '2025/2026',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'is_current' => true,
    ]);

    $invoice = Invoice::create([
        'user_id' => $user1->id,
        'reference' => 'HST-TEST-1001',
        'type' => 'hostel_fee',
        'amount' => 50000,
        'status' => 'pending',
    ]);

    $booking = HostelBooking::create([
        'student_id' => $student1->id,
        'session_id' => $session1->id,
        'hostel_room_id' => $room->id,
        'invoice_id' => $invoice->id,
        'status' => 'pending',
    ]);

    $service = SquadcoService::forInvoice($invoice->fresh());

    expect($service->getSecretKey())->toBe('hostel_a_custom_secret_key');
});

test('paystack service resolves custom secret key for specific hostel invoice', function () {
    config(['services.paystack.secret_key' => 'global_paystack_key_456']);

    $hostel = Hostel::create([
        'name' => 'Hostel Paystack',
        'gender_type' => 'female',
        'description' => 'Test Hostel Paystack',
        'payment_gateway' => 'paystack',
        'paystack_secret_key' => 'hostel_paystack_custom_secret_key',
        'is_visible' => true,
    ]);

    $block = HostelBlock::create([
        'hostel_id' => $hostel->id,
        'name' => 'Block P',
        'is_visible' => true,
    ]);

    $floor = HostelFloor::create([
        'hostel_block_id' => $block->id,
        'name' => 'Ground Floor',
        'is_visible' => true,
    ]);

    $room = HostelRoom::create([
        'hostel_floor_id' => $floor->id,
        'room_number' => '201',
        'capacity' => 4,
        'is_visible' => true,
    ]);

    $user2 = \App\Models\User::factory()->create();
    $student2 = \App\Models\Student::create([
        'user_id' => $user2->id,
        'matriculation_number' => 'MAT-' . rand(1000, 9999),
        'status' => 'active',
    ]);
    $session2 = \App\Models\Session::create([
        'name' => '2025/2026-B',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'is_current' => false,
    ]);

    $invoice = Invoice::create([
        'user_id' => $user2->id,
        'reference' => 'HST-TEST-2001',
        'type' => 'hostel_fee',
        'amount' => 60000,
        'status' => 'pending',
    ]);

    $booking = HostelBooking::create([
        'student_id' => $student2->id,
        'session_id' => $session2->id,
        'hostel_room_id' => $room->id,
        'invoice_id' => $invoice->id,
        'status' => 'pending',
    ]);

    $service = PaystackService::forInvoice($invoice->fresh());

    expect($service->getSecretKey())->toBe('hostel_paystack_custom_secret_key');
});
