<?php

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createTestStudentWithDepartment(): User
{
    Permission::findOrCreate('access_student_portal');

    $user = User::create([
        'name' => 'Student ' . uniqid(),
        'email' => 'student_' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $user->givePermissionTo('access_student_portal');

    $dept = \App\Models\Department::create([
        'name' => 'Computer Science Dept ' . uniqid(),
        'code' => 'CS' . rand(100, 999),
    ]);

    Student::create([
        'user_id' => $user->id,
        'matriculation_number' => 'MAT-' . uniqid(),
        'department_id' => $dept->id,
        'entry_mode' => 'UTME',
        'level' => 100,
        'status' => 'active',
    ]);

    return $user;
}

function createTestApplicantUser(): User
{
    Permission::findOrCreate('access_applicant_portal');

    $user = User::create([
        'name' => 'Applicant ' . uniqid(),
        'email' => 'applicant_' . uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);
    $user->givePermissionTo('access_applicant_portal');

    Applicant::create([
        'user_id' => $user->id,
        'application_number' => 'APP-' . uniqid(),
        'status' => 'pending',
    ]);

    return $user;
}

test('student payment requires agreed_to_policy to initiate payment', function () {
    $user = createTestStudentWithDepartment();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-TEST-001',
        'type' => 'school_fee',
        'amount' => 50000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    // Attempting payment without agreed_to_policy
    $response = $this->actingAs($user)->post(route('student.payments.pay', $invoice->id), [
        'amount' => 50000,
    ]);

    $response->assertSessionHasErrors(['agreed_to_policy']);
    expect(Payment::count())->toBe(0);

    // Attempting payment with agreed_to_policy = false
    $response = $this->actingAs($user)->post(route('student.payments.pay', $invoice->id), [
        'amount' => 50000,
        'agreed_to_policy' => false,
    ]);

    $response->assertSessionHasErrors(['agreed_to_policy']);
    expect(Payment::count())->toBe(0);
});

test('applicant payment requires agreed_to_policy to initiate payment', function () {
    $user = createTestApplicantUser();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-APP-001',
        'type' => 'application_fee',
        'amount' => 10000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    // Attempting without policy acceptance
    $response = $this->actingAs($user)->post(route('applicant.payment.pay'), []);

    $response->assertSessionHasErrors(['agreed_to_policy']);
    expect(Payment::count())->toBe(0);
});

test('payment initialization creates payment record with policy_agreed_at populated', function () {
    $user = createTestApplicantUser();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-APP-002',
        'type' => 'application_fee',
        'amount' => 10000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    // Mock payment gateway HTTP call
    Http::fake([
        '*paystack*' => Http::response([
            'status' => true,
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/test-auth-url',
                'reference' => 'PAY-TEST-REF',
            ]
        ], 200),
        '*seerbit*' => Http::response([
            'status' => 'SUCCESS',
            'data' => [
                'redirectUrl' => 'https://checkout.seerbit.com/test',
            ]
        ], 200),
        '*squadco*' => Http::response([
            'status' => 200,
            'data' => [
                'checkout_url' => 'https://checkout.squadco.com/test',
            ]
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('applicant.payment.pay'), [
        'agreed_to_policy' => true,
    ]);

    $payment = Payment::where('invoice_id', $invoice->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->policy_agreed_at)->not->toBeNull();
});
