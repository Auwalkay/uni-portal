<?php

namespace Tests\Feature;

use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use App\Models\HostelBooking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CancelOverdueHostelBookingsTest extends TestCase
{
    use RefreshDatabase;

    protected $studentUser;
    protected $student;
    protected $session;
    protected $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create(['name' => 'Computer Science', 'type' => 'UG', 'department_id' => $dept->id]);

        $this->studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@portal.com',
            'password' => bcrypt('password'),
        ]);
        $this->studentUser->assignRole('student');

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'matric_number' => 'MAT123',
            'current_level' => 100,
            'programme_id' => $prog->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'gender' => 'Male',
        ]);

        $this->session = Session::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-01',
            'is_current' => true,
        ]);

        $hostel = Hostel::create([
            'name' => 'Male Hostel A',
            'gender_type' => 'male',
        ]);

        $block = HostelBlock::create([
            'hostel_id' => $hostel->id,
            'name' => 'Block 1',
        ]);

        // Find or create Floor
        $floor = HostelFloor::create([
            'hostel_block_id' => $block->id,
            'name' => 'Ground Floor',
        ]);

        $this->room = HostelRoom::create([
            'hostel_floor_id' => $floor->id,
            'room_number' => 'Room 101',
            'capacity' => 4,
        ]);
    }

    public function test_overdue_pending_hostel_booking_without_payments_is_cancelled()
    {
        // 1. Create an invoice with due date in the past
        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'HST-OVERDUE',
            'type' => 'hostel_fee',
            'amount' => 15000,
            'status' => 'pending',
            'due_date' => now()->subDays(1),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Hostel Accommodation Fee',
            'amount' => 15000,
        ]);

        // 2. Create the pending booking
        $booking = HostelBooking::create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'hostel_room_id' => $this->room->id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('hostel_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);

        // 3. Run the scheduler command
        $this->artisan('hostels:cancel-overdue')
            ->expectsOutput('Checking for overdue hostel bookings...')
            ->expectsOutput('Found 1 potentially overdue hostel bookings.')
            ->expectsOutput("Cancelling overdue pending booking for student ID: {$this->student->id}")
            ->expectsOutput('Successfully cancelled and nullified 1 overdue hostel bookings.')
            ->assertExitCode(0);

        // 4. Assert that the booking and the invoice have been deleted
        $this->assertDatabaseMissing('hostel_bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_overdue_pending_hostel_booking_is_not_cancelled_if_payment_verifies_successfully()
    {
        // 1. Create an invoice with due date in the past
        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'HST-PAID',
            'type' => 'hostel_fee',
            'amount' => 15000,
            'status' => 'pending',
            'due_date' => now()->subDays(1),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Hostel Accommodation Fee',
            'amount' => 15000,
        ]);

        // 2. Create the pending booking
        $booking = HostelBooking::create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'hostel_room_id' => $this->room->id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);

        // 3. Create a pending payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $this->studentUser->id,
            'gateway_reference' => 'PAYSTACK-123',
            'amount' => 15000,
            'status' => 'pending',
            'channel' => 'card',
        ]);
        $payment->update(['gateway' => 'paystack']);

        // 4. Mock the Paystack verifyTransaction API endpoint
        Http::fake([
            'https://api.paystack.co/transaction/verify/PAYSTACK-123' => Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'success',
                    'reference' => 'PAYSTACK-123',
                    'amount' => 15000 * 100, // kobo
                    'gateway_response' => 'Successful',
                    'channel' => 'card',
                ]
            ], 200),
        ]);

        // 5. Run the scheduler command
        $this->artisan('hostels:cancel-overdue')
            ->expectsOutput('Checking for overdue hostel bookings...')
            ->expectsOutput('Found 1 potentially overdue hostel bookings.')
            ->expectsOutput("Verifying pending payment: PAYSTACK-123 for student booking...")
            ->expectsOutput('✓ Booking payment PAYSTACK-123 verified as SUCCESS. Booking confirmed.')
            ->expectsOutput('Successfully cancelled and nullified 0 overdue hostel bookings.')
            ->assertExitCode(0);

        // 6. Assert that the booking is confirmed and not deleted
        $booking->refresh();
        $invoice->refresh();
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('paid', $invoice->status);
        $this->assertDatabaseHas('hostel_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_booking_is_not_cancelled_if_due_date_is_in_the_future()
    {
        // 1. Create an invoice with due date in the future
        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'HST-FUTURE',
            'type' => 'hostel_fee',
            'amount' => 15000,
            'status' => 'pending',
            'due_date' => now()->addDays(5),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Hostel Accommodation Fee',
            'amount' => 15000,
        ]);

        // 2. Create the pending booking
        $booking = HostelBooking::create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'hostel_room_id' => $this->room->id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);

        // 3. Run the scheduler command
        $this->artisan('hostels:cancel-overdue')
            ->expectsOutput('Checking for overdue hostel bookings...')
            ->expectsOutput('Found 0 potentially overdue hostel bookings.')
            ->assertExitCode(0);

        // 4. Assert that the booking and invoice are preserved
        $this->assertDatabaseHas('hostel_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
