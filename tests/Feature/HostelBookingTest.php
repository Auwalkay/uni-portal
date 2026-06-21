<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use App\Models\HostelFee;
use App\Models\Invoice;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HostelBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentUser;
    protected Student $student;
    protected Session $session;
    protected Hostel $maleHostel;
    protected Hostel $femaleHostel;
    protected HostelRoom $maleRoom;
    protected HostelRoom $femaleRoom;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create admin user
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        // Create active session
        $this->session = Session::create([
            'name' => '2025/2026',
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(11),
            'is_current' => true,
        ]);

        // Create male student
        $this->studentUser = User::create([
            'name' => 'Male Student',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->studentUser->assignRole('student');

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'matriculation_number' => 'MAT-MALE-1',
            'gender' => 'male',
        ]);

        // Create male hostel, block, floor, room
        $this->maleHostel = Hostel::create([
            'name' => 'Male Hall',
            'gender_type' => 'male',
            'description' => 'Male hostel description',
        ]);

        $maleBlock = HostelBlock::create([
            'hostel_id' => $this->maleHostel->id,
            'name' => 'Block A',
        ]);

        $maleFloor = HostelFloor::create([
            'hostel_block_id' => $maleBlock->id,
            'name' => 'Ground Floor',
        ]);

        $this->maleRoom = HostelRoom::create([
            'hostel_floor_id' => $maleFloor->id,
            'room_number' => '101',
            'capacity' => 4,
        ]);

        // Create female hostel, block, floor, room
        $this->femaleHostel = Hostel::create([
            'name' => 'Female Hall',
            'gender_type' => 'female',
            'description' => 'Female hostel description',
        ]);

        $femaleBlock = HostelBlock::create([
            'hostel_id' => $this->femaleHostel->id,
            'name' => 'Block B',
        ]);

        $femaleFloor = HostelFloor::create([
            'hostel_block_id' => $femaleBlock->id,
            'name' => 'First Floor',
        ]);

        $this->femaleRoom = HostelRoom::create([
            'hostel_floor_id' => $femaleFloor->id,
            'room_number' => '201',
            'capacity' => 4,
        ]);

        // Set up fees
        HostelFee::create([
            'session_id' => $this->session->id,
            'amount' => 50000.00,
        ]);
    }

    public function test_admin_can_search_students()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.hostels.search-students', ['query' => 'Male']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Male Student',
            'email' => 'student@portal.com',
        ]);
    }

    public function test_available_rooms_filters_by_student_gender()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.hostels.rooms.available', ['student_id' => $this->student->id]));

        $response->assertStatus(200);
        
        // Assert it returns the male hostel but NOT the female hostel
        $response->assertJsonFragment(['name' => 'Male Hall']);
        $response->assertJsonMissing(['name' => 'Female Hall']);
    }

    public function test_allocation_fails_if_student_has_not_paid_school_fees()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.hostels.bookings.store'), [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
        ]);

        $response->assertSessionHas('error', 'Cannot allocate room. This student has not paid their school fees for the current session.');
    }

    public function test_allocation_succeeds_if_student_has_paid_school_fees()
    {
        $this->actingAs($this->admin);

        // Simulate paid school fees
        Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'SCH-FEES-1',
            'type' => 'school_fee',
            'amount' => 100000.00,
            'status' => 'paid',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->post(route('admin.hostels.bookings.store'), [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
        ]);

        $response->assertSessionHas('success', 'Hostel room allocated successfully for the student!');
        $this->assertDatabaseHas('hostel_bookings', [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
            'status' => 'pending',
        ]);
    }

    public function test_direct_confirmation_marks_booking_confirmed_and_payment_manual()
    {
        $this->actingAs($this->admin);

        // Simulate paid school fees
        Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'SCH-FEES-1',
            'type' => 'school_fee',
            'amount' => 100000.00,
            'status' => 'paid',
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->post(route('admin.hostels.bookings.store'), [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
            'mark_as_paid' => true,
        ]);

        $response->assertSessionHas('success', 'Hostel room allocated successfully for the student!');
        
        $this->assertDatabaseHas('hostel_bookings', [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->studentUser->id,
            'type' => 'hostel_fee',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->studentUser->id,
            'channel' => 'manual',
            'status' => 'success',
        ]);
    }

    public function test_hostel_fee_payment_requires_75_percent_minimum_or_full_payment()
    {
        // acting as student
        $this->actingAs($this->studentUser);

        // create a hostel fee invoice
        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'HST-FEES-TEST',
            'type' => 'hostel_fee',
            'amount' => 10000.00,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
        ]);

        // Attempting to pay 50% (5000) - should fail
        $response = $this->post(route('student.payments.pay', $invoice->id), [
            'amount' => 5000,
        ]);
        $response->assertSessionHas('error');
        $this->assertTrue(str_contains(session('error'), 'Minimum required upfront payment is 7,500'));

        // Mock payment gateway interface so initialization doesn't throw or fail
        $this->mock(\App\Contracts\PaymentGatewayInterface::class, function ($mock) {
            $mock->shouldReceive('initializeTransaction')->andReturn([
                'authorization_url' => 'https://example.com/pay',
            ]);
        });

        // Attempting to pay 75% (7500) - should succeed
        $response = $this->post(route('student.payments.pay', $invoice->id), [
            'amount' => 7500,
        ]);
        $response->assertStatus(409)->orExpect(true); // Should redirect/location or 302/Inertia location (which Inertia returns as 409 conflict with X-Inertia-Location header)
        if ($response->getStatusCode() === 409) {
            $response->assertHeader('X-Inertia-Location');
        } else {
            $response->assertRedirect();
        }

        // Attempting to pay 100% (10000) - should succeed
        $response = $this->post(route('student.payments.pay', $invoice->id), [
            'amount' => 10000,
        ]);
        if ($response->getStatusCode() === 409) {
            $response->assertHeader('X-Inertia-Location');
        } else {
            $response->assertRedirect();
        }
    }

    public function test_manual_invoice_payment_confirms_hostel_booking()
    {
        $this->actingAs($this->admin);

        // 1. Simulate school fees payment
        $schoolInvoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'SCH-FEES-1',
            'type' => 'school_fee',
            'amount' => 100000.00,
            'status' => 'paid',
            'due_date' => now()->addDays(7),
        ]);

        // 2. Allocate Room (creates booking in pending status and hostel_fee invoice in pending status)
        $this->post(route('admin.hostels.bookings.store'), [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
        ]);

        $hostelInvoice = Invoice::where('user_id', $this->studentUser->id)
            ->where('type', 'hostel_fee')
            ->firstOrFail();

        $this->assertDatabaseHas('hostel_bookings', [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
            'status' => 'pending',
        ]);

        // 3. Mark the hostel_fee invoice as paid manually by admin
        $response = $this->post(route('admin.invoices.mark-as-paid', $hostelInvoice->id), [
            'amount' => $hostelInvoice->amount,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302); // redirects back

        // 4. Assert booking status is updated to confirmed
        $this->assertDatabaseHas('hostel_bookings', [
            'student_id' => $this->student->id,
            'hostel_room_id' => $this->maleRoom->id,
            'status' => 'confirmed',
        ]);

        // 5. Assert invoice status is updated to paid
        $hostelInvoice->refresh();
        $this->assertEquals('paid', $hostelInvoice->status);
    }
}
