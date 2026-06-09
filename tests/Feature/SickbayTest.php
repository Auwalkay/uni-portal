<?php

namespace Tests\Feature;

use App\Models\SickbayItem;
use App\Models\SickbayVisit;
use App\Models\SickbayMedicalLog;
use App\Models\Student;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SickbayTest extends TestCase
{
    use RefreshDatabase;

    protected User $nurse;
    protected User $studentUser;
    protected User $staffUser;
    protected Student $student;
    protected Staff $staff;
    protected SickbayItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create nurse
        $this->nurse = User::create([
            'name' => 'Nurse Jenkins',
            'email' => 'nurse@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->nurse->assignRole('sickbay_nurse');

        // Create student user & student record
        $this->studentUser = User::create([
            'name' => 'John Student',
            'email' => 'john@student.com',
            'password' => Hash::make('password'),
        ]);
        $this->studentUser->assignRole('student');

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'matriculation_number' => 'MAT123456',
            'next_of_kin_name' => 'Mary Parent',
            'next_of_kin_phone' => '08012345678',
            'next_of_kin_address' => '123 Main Street',
        ]);

        // Create staff user & staff record
        $this->staffUser = User::create([
            'name' => 'Jane Staff',
            'email' => 'jane@staff.com',
            'password' => Hash::make('password'),
        ]);
        $this->staffUser->assignRole('staff');

        $this->staff = Staff::create([
            'user_id' => $this->staffUser->id,
            'staff_number' => 'STF998877',
            'next_of_kin_name' => 'John Kin',
            'next_of_kin_phone' => '08099998888',
        ]);

        // Create first aid supply item
        $this->item = SickbayItem::create([
            'name' => 'Paracetamol 500mg',
            'category' => 'OTC Drug',
            'stock_quantity' => 50,
            'alert_threshold' => 10,
        ]);
    }

    public function test_student_can_view_sickbay_portal()
    {
        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.sickbay.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Sickbay/Index')
                ->has('visits')
                ->has('student')
        );
    }

    public function test_nurse_can_view_admin_sickbay_portal()
    {
        $this->actingAs($this->nurse);

        $response = $this->get(route('admin.sickbay.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Sickbay/Index')
                ->has('activeVisits')
                ->has('stats')
        );
    }

    public function test_nurse_can_search_students()
    {
        $this->actingAs($this->nurse);

        $response = $this->get(route('admin.sickbay.students.search', ['query' => 'John']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'John Student',
            'matriculation_number' => 'MAT123456',
        ]);
    }

    public function test_nurse_can_search_staff()
    {
        $this->actingAs($this->nurse);

        $response = $this->get(route('admin.sickbay.students.search', ['query' => 'Jane']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Jane Staff',
            'matriculation_number' => 'STF998877',
            'type' => 'Staff',
        ]);
    }

    public function test_nurse_can_check_in_walk_in()
    {
        $this->actingAs($this->nurse);

        $response = $this->post(route('admin.sickbay.check_in'), [
            'user_id' => $this->studentUser->id,
            'symptoms' => 'Severe headache and fatigue',
            'visit_type' => 'walk_in',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sickbay_visits', [
            'user_id' => $this->studentUser->id,
            'symptoms' => 'Severe headache and fatigue',
            'status' => 'waiting',
        ]);
    }

    public function test_nurse_can_record_treatment_and_deduct_inventory()
    {
        $visit = SickbayVisit::create([
            'user_id' => $this->studentUser->id,
            'attended_by' => $this->nurse->id,
            'check_in_at' => now(),
            'symptoms' => 'Fever',
            'visit_type' => 'walk_in',
            'status' => 'waiting',
        ]);

        $this->actingAs($this->nurse);

        $response = $this->post(route('admin.sickbay.treatment.store', $visit->id), [
            'blood_pressure' => '120/80',
            'temperature' => 38.5,
            'weight' => 70.0,
            'findings' => 'High fever, likely malaria',
            'treatment_given' => 'Administered paracetamol, advised rest.',
            'medicines_dispensed' => [
                [
                    'id' => $this->item->id,
                    'name' => $this->item->name,
                    'quantity' => 2,
                ]
            ],
            'parent_contacted' => true,
            'parent_contact_notes' => 'Called mother to inform about high temperature.',
            'referral_hospital' => '',
            'referral_notes' => '',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('sickbay_visits', [
            'id' => $visit->id,
            'status' => 'treated',
            'check_out_at' => now()->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('sickbay_medical_logs', [
            'sickbay_visit_id' => $visit->id,
            'temperature' => 38.5,
            'findings' => 'High fever, likely malaria',
            'parent_contacted' => true,
        ]);

        // Assert quantity decremented
        $this->assertEquals(48, $this->item->fresh()->stock_quantity);
    }

    public function test_nurse_can_assign_and_discharge_bed()
    {
        $visit = SickbayVisit::create([
            'user_id' => $this->studentUser->id,
            'attended_by' => $this->nurse->id,
            'check_in_at' => now(),
            'symptoms' => 'Dizziness',
            'visit_type' => 'walk_in',
            'status' => 'waiting',
        ]);

        $this->actingAs($this->nurse);

        // Assign bed
        $response = $this->post(route('admin.sickbay.beds.assign', $visit->id), [
            'bed_number' => 'Bed 3',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sickbay_visits', [
            'id' => $visit->id,
            'bed_number' => 'Bed 3',
            'status' => 'under_observation',
        ]);

        // Discharge bed
        $response = $this->post(route('admin.sickbay.beds.discharge', $visit->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('sickbay_visits', [
            'id' => $visit->id,
            'status' => 'discharged',
            'check_out_at' => now()->toDateTimeString(),
        ]);
    }

    public function test_nurse_can_add_inventory_item()
    {
        $this->actingAs($this->nurse);

        $response = $this->post(route('admin.sickbay.inventory.store'), [
            'name' => 'Ibuprofen 200mg',
            'category' => 'OTC Drug',
            'stock_quantity' => 100,
            'alert_threshold' => 15,
            'expiry_date' => '2027-12-31',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sickbay_items', [
            'name' => 'Ibuprofen 200mg',
            'stock_quantity' => 100,
        ]);
    }

    public function test_staff_without_manage_permissions_gets_redirected_from_admin_sickbay_to_history()
    {
        $this->actingAs($this->staffUser);

        $response = $this->get(route('admin.sickbay.index'));

        $response->assertRedirect(route('admin.sickbay.history'));
    }

    public function test_staff_without_manage_permissions_gets_redirected_from_beds_to_history()
    {
        $this->actingAs($this->staffUser);

        $response = $this->get(route('admin.sickbay.beds.index'));

        $response->assertRedirect(route('admin.sickbay.history'));
    }

    public function test_staff_can_view_admin_sickbay_history()
    {
        $this->actingAs($this->staffUser);

        $response = $this->get(route('admin.sickbay.history'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Sickbay/History')
                ->has('visits')
                ->has('staff')
        );
    }
}
