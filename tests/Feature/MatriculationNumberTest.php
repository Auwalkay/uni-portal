<?php

namespace Tests\Feature;

use App\Helpers\MatriculationNumberHelper;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatriculationNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_helper_generates_correct_digit_matric_number_sequence()
    {
        // 1. Setup system setting
        SystemSetting::set('matric_format', 'MIU{YEAR}{DEPT}{SEQUENCE}');

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);

        $year = date('y');

        // Level 100
        $matric1 = MatriculationNumberHelper::generate([
            'dept_code' => 'CSC',
            'level' => '100',
        ]);
        // Expected: MIU{YEAR}CSC1001
        $this->assertEquals("MIU{$year}CSC1001", $matric1);

        // Save a student with this matric number to increment the count
        $user1 = User::factory()->create();
        Student::create([
            'user_id' => $user1->id,
            'matriculation_number' => $matric1,
            'current_level' => '100',
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
        ]);

        $matric2 = MatriculationNumberHelper::generate([
            'dept_code' => 'CSC',
            'level' => '100',
        ]);
        $this->assertEquals("MIU{$year}CSC1002", $matric2);

        // Level 200 (should continue from Level 100 sequence counter, expecting 2002 instead of starting fresh at 2001)
        $matric3 = MatriculationNumberHelper::generate([
            'dept_code' => 'CSC',
            'level' => '200',
        ]);
        $this->assertEquals("MIU{$year}CSC2002", $matric3);

        // Save the level 200 student
        $user2 = User::factory()->create();
        Student::create([
            'user_id' => $user2->id,
            'matriculation_number' => $matric3,
            'current_level' => '200',
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
        ]);

        // Next Level 200 student should continue to 2003
        $matric4 = MatriculationNumberHelper::generate([
            'dept_code' => 'CSC',
            'level' => '200',
        ]);
        $this->assertEquals("MIU{$year}CSC2003", $matric4);
    }

    public function test_all_caps_enforced_for_generated_and_manual_inputs()
    {
        SystemSetting::set('matric_format', 'miu{YEAR}{dept}{SEQUENCE}');

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'sci']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'csc', 'faculty_id' => $faculty->id]);

        $year = date('y');

        // Test Helper always outputs uppercase
        $matric = MatriculationNumberHelper::generate([
            'dept_code' => 'csc',
            'level' => '100',
        ]);
        $this->assertEquals(strtoupper("MIU{$year}CSC1001"), $matric);

        // Test manual student store/update forces uppercase
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $session = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'is_current' => true,
        ]);
        $semester = $session->semesters()->create([
            'name' => 'First Semester',
            'is_current' => true,
        ]);

        $programme = Programme::create([
            'name' => 'Computer Science',
            'department_id' => $dept->id,
            'type' => 'UG',
        ]);

        $state = \App\Models\State::create(['name' => 'Federal Capital Territory']);
        $lga = \App\Models\Lga::create(['name' => 'Abuja', 'state_id' => $state->id]);

        $studentData = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone_number' => '08012345678',
            'gender' => 'female',
            'dob' => '2002-05-15',
            'address' => '456 Uni Rd',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $programme->id,
            'current_level' => '200',
            'admitted_session_id' => $session->id,
            'entry_mode' => 'UTME',
            'matriculation_number' => 'miu/26/csc/2005', // Lowercase input
            'fee_policy' => 'admission_session',
        ];

        // 1. Store action
        $response = $this->actingAs($admin)->post(route('admin.students.store'), $studentData);
        $response->assertSessionHasNoErrors();

        $student = Student::where('gender', 'female')->first();
        $this->assertEquals('MIU/26/CSC/2005', $student->matriculation_number);

        // 2. Update action
        $studentData['matriculation_number'] = 'miu/26/csc/9999'; // New lowercase input
        $response = $this->actingAs($admin)->put(route('admin.students.update', $student->id), $studentData);
        $response->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertEquals('MIU/26/CSC/9999', $student->matriculation_number);
    }

    public function test_program_duration_is_deducted_based_on_entry_level()
    {
        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'sci']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'csc', 'faculty_id' => $faculty->id]);
        $programme = Programme::create([
            'name' => 'Computer Science',
            'department_id' => $dept->id,
            'type' => 'UG',
            'duration' => 4, // 4 years normal duration
        ]);

        $session = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'is_current' => true,
        ]);
        $semester = $session->semesters()->create([
            'name' => 'First Semester',
            'is_current' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_duration@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $state = \App\Models\State::create(['name' => 'Kano State']);
        $lga = \App\Models\Lga::create(['name' => 'Dala', 'state_id' => $state->id]);

        // Test manual creation at 100 level (UTME) -> duration should be 4
        $student1Data = [
            'first_name' => 'John',
            'last_name' => 'UTME',
            'email' => 'john.utme@example.com',
            'phone_number' => '08011111111',
            'gender' => 'male',
            'dob' => '2004-01-01',
            'address' => '123 street',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $programme->id,
            'current_level' => '100',
            'admitted_session_id' => $session->id,
            'entry_mode' => 'UTME',
            'fee_policy' => 'admission_session',
        ];
        $this->actingAs($admin)->post(route('admin.students.store'), $student1Data);
        $student1 = Student::whereHas('user', function($q) {
            $q->where('email', 'john.utme@example.com');
        })->first();
        $this->assertNotNull($student1);
        $this->assertEquals(4, $student1->program_duration);

        // Test manual creation at 200 level -> duration should be 3 (4 - 1)
        $student2Data = [
            'first_name' => 'Jane',
            'last_name' => 'DE',
            'email' => 'jane.de@example.com',
            'phone_number' => '08022222222',
            'gender' => 'female',
            'dob' => '2003-01-01',
            'address' => '123 street',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $programme->id,
            'current_level' => '200',
            'admitted_session_id' => $session->id,
            'entry_mode' => 'Direct Entry',
            'fee_policy' => 'admission_session',
        ];
        $this->actingAs($admin)->post(route('admin.students.store'), $student2Data);
        $student2 = Student::whereHas('user', function($q) {
            $q->where('email', 'jane.de@example.com');
        })->first();
        $this->assertNotNull($student2);
        $this->assertEquals(3, $student2->program_duration);

        // Test manual creation at 300 level -> duration should be 2 (4 - 2)
        $student3Data = [
            'first_name' => 'Dave',
            'last_name' => 'Transfer',
            'email' => 'dave.transfer@example.com',
            'phone_number' => '08033333333',
            'gender' => 'male',
            'dob' => '2002-01-01',
            'address' => '123 street',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $programme->id,
            'current_level' => '300',
            'admitted_session_id' => $session->id,
            'entry_mode' => 'Transfer',
            'fee_policy' => 'admission_session',
        ];
        $this->actingAs($admin)->post(route('admin.students.store'), $student3Data);
        $student3 = Student::whereHas('user', function($q) {
            $q->where('email', 'dave.transfer@example.com');
        })->first();
        $this->assertNotNull($student3);
        $this->assertEquals(2, $student3->program_duration);
    }
}
