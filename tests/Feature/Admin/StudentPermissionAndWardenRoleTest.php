<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Student;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\Lga;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPermissionAndWardenRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staffWithEditPermission;
    protected User $staffWithoutEditPermission;
    protected Student $student;
    protected array $studentUpdateData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create Admin
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        // Create Staff with general edit_students permission
        $this->staffWithEditPermission = User::create([
            'name' => 'General Editor Staff',
            'email' => 'editor@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->staffWithEditPermission->assignRole('staff');
        // Give general edit_students permission but NOT edit_student_name_email
        $this->staffWithEditPermission->givePermissionTo('edit_students');

        // Create Staff without edit_students permission
        $this->staffWithoutEditPermission = User::create([
            'name' => 'Normal Staff',
            'email' => 'staff@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->staffWithoutEditPermission->assignRole('staff');

        // Setup Student dependencies
        $session = Session::create([
            'name' => '2025/2026',
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(11),
            'is_current' => true,
        ]);
        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $program = Programme::create(['name' => 'B.Sc. Computer Science', 'department_id' => $dept->id, 'duration' => 4]);
        $state = State::create(['name' => 'Lagos']);
        $lga = Lga::create(['name' => 'Ikeja', 'state_id' => $state->id]);

        $studentUser = User::create([
            'name' => 'Original Student Name',
            'email' => 'student.original@portal.com',
            'password' => Hash::make('password'),
        ]);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'matriculation_number' => 'MIU/25/CSC/1001',
            'gender' => 'male',
            'dob' => '2001-01-01',
            'phone_number' => '08012345678',
            'address' => '123 University Rd',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $program->id,
            'current_level' => '100',
            'admitted_session_id' => $session->id,
            'entry_mode' => 'UTME',
            'fee_policy' => 'admission_session',
        ]);

        $this->studentUpdateData = [
            'first_name' => 'Original',
            'last_name' => 'Student Name',
            'email' => 'student.original@portal.com',
            'phone_number' => '08099999999', // Updated phone number
            'gender' => 'male',
            'dob' => '2001-01-01',
            'address' => '123 University Rd',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $program->id,
            'current_level' => '100',
            'admitted_session_id' => $session->id,
            'entry_mode' => 'UTME',
            'matriculation_number' => 'MIU/25/CSC/1001',
            'fee_policy' => 'admission_session',
        ];
    }

    public function test_staff_with_general_edit_permission_cannot_update_name()
    {
        $this->actingAs($this->staffWithEditPermission);

        $payload = $this->studentUpdateData;
        $payload['first_name'] = 'HackedFirst'; // Change name

        $response = $this->put(route('admin.students.update', $this->student->id), $payload);
        $response->assertStatus(403);

        $this->student->user->refresh();
        $this->assertEquals('Original Student Name', $this->student->user->name);
    }

    public function test_staff_with_general_edit_permission_cannot_update_email()
    {
        $this->actingAs($this->staffWithEditPermission);

        $payload = $this->studentUpdateData;
        $payload['email'] = 'hacked@portal.com'; // Change email

        $response = $this->put(route('admin.students.update', $this->student->id), $payload);
        $response->assertStatus(403);

        $this->student->user->refresh();
        $this->assertEquals('student.original@portal.com', $this->student->user->email);
    }

    public function test_staff_with_general_edit_permission_can_update_other_details_without_changing_name_email()
    {
        $this->actingAs($this->staffWithEditPermission);

        $payload = $this->studentUpdateData; // Name/email unchanged
        $payload['phone_number'] = '08055555555'; // Change phone number

        $response = $this->put(route('admin.students.update', $this->student->id), $payload);
        $response->assertStatus(302); // Redirect back on success
        $response->assertSessionHasNoErrors();

        $this->student->refresh();
        $this->assertEquals('08055555555', $this->student->phone_number);
    }

    public function test_admin_with_edit_name_email_permission_can_update_name_and_email()
    {
        $this->actingAs($this->admin); // Admin has all permissions including edit_student_name_email

        $payload = $this->studentUpdateData;
        $payload['first_name'] = 'AdminUpdated';
        $payload['last_name'] = 'LastName';
        $payload['email'] = 'adminupdated@portal.com';

        $response = $this->put(route('admin.students.update', $this->student->id), $payload);
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $this->student->user->refresh();
        $this->assertEquals('AdminUpdated LastName', $this->student->user->name);
        $this->assertEquals('adminupdated@portal.com', $this->student->user->email);
    }

    public function test_hostel_warden_role_has_correct_permissions()
    {
        $role = Role::findByName('hostel_warden');
        $this->assertNotNull($role);

        $expectedPermissions = [
            'access_admin_dashboard',
            'view_students',
            'manage_hostels',
            'manage_hostel_bookings',
        ];

        foreach ($expectedPermissions as $perm) {
            $this->assertTrue($role->hasPermissionTo($perm));
        }

        // Warden should NOT have invoice or result editing permissions
        $this->assertFalse($role->hasPermissionTo('edit_results'));
        $this->assertFalse($role->hasPermissionTo('create_invoices'));
    }
}
