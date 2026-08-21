<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Hostel;
use App\Models\HostelBlock;
use App\Models\HostelBooking;
use App\Models\HostelFloor;
use App\Models\HostelRoom;
use App\Models\Invoice;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAccommodationViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_admin_can_view_student_accommodation_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $session = Session::factory()->create(['is_current' => true]);
        $studentUser = User::factory()->create(['name' => 'John Hostel Student']);
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $hostel = Hostel::create([
            'name' => 'Mandela Hall',
            'gender_type' => 'male',
            'is_visible' => true,
        ]);
        $block = HostelBlock::create(['hostel_id' => $hostel->id, 'name' => 'Block A']);
        $floor = HostelFloor::create(['hostel_block_id' => $block->id, 'name' => 'First Floor']);
        $room = HostelRoom::create([
            'hostel_floor_id' => $floor->id,
            'room_number' => '105',
            'capacity' => 4,
            'is_visible' => true,
        ]);

        $invoice = Invoice::create([
            'user_id' => $studentUser->id,
            'session_id' => $session->id,
            'invoice_number' => 'INV-HOSTEL-001',
            'reference' => 'REF-HOSTEL-001',
            'invoice_type' => 'hostel_fee',
            'amount' => 50000,
            'status' => 'paid',
        ]);

        $booking = HostelBooking::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'hostel_room_id' => $room->id,
            'invoice_id' => $invoice->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.students.show', $student->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Students/Show')
            ->has('student.hostel_bookings', 1)
            ->where('student.hostel_bookings.0.room.room_number', '105')
            ->where('student.hostel_bookings.0.room.floor.block.hostel.name', 'Mandela Hall')
        );
    }
}
