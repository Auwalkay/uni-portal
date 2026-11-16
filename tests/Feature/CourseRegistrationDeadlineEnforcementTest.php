<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourseRegistrationDeadlineEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $student;
    protected $session;
    protected $firstSemester;
    protected $secondSemester;
    protected $courseSem1;
    protected $courseSem2;
    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('access_student_portal');

        $this->user = User::create([
            'name' => 'Registration Test Student',
            'email' => 'reg_student_' . uniqid() . '@portal.edu',
            'password' => Hash::make('password'),
        ]);
        $this->user->givePermissionTo('access_student_portal');

        $faculty = Faculty::create([
            'name' => 'Faculty of Science',
            'code' => 'SCI',
            'is_active' => true,
        ]);

        $department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);

        $programme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'department_id' => $department->id,
            'max_credit_units' => 24,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->user->id,
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'program_id' => $programme->id,
            'level' => '100',
            'matriculation_number' => 'MAT/' . rand(1000, 9999),
        ]);

        $this->session = Session::create([
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
            'registration_enabled' => true,
            'school_fee_payment_enabled' => true,
        ]);

        $this->firstSemester = Semester::create([
            'session_id' => $this->session->id,
            'name' => 'First Semester',
            'is_current' => true,
            'registration_starts_at' => now()->subMonth(),
            'registration_ends_at' => now()->addMonth(),
        ]);

        $this->secondSemester = Semester::create([
            'session_id' => $this->session->id,
            'name' => 'Second Semester',
            'is_current' => false,
            'registration_starts_at' => now()->subMonth(),
            'registration_ends_at' => now()->addMonth(),
        ]);

        $this->courseSem1 = Course::create([
            'code' => 'CSC 101',
            'title' => 'Intro to Computer Science',
            'units' => 3,
            'department_id' => $department->id,
            'level' => 100,
            'semester' => '1',
            'is_active' => true,
        ]);

        $this->courseSem2 = Course::create([
            'code' => 'CSC 102',
            'title' => 'Data Structures',
            'units' => 3,
            'department_id' => $department->id,
            'level' => 100,
            'semester' => '2',
            'is_active' => true,
        ]);

        // Default: Full school fee paid
        $this->invoice = Invoice::create([
            'reference' => 'INV-' . uniqid(),
            'user_id' => $this->user->id,
            'session_id' => $this->session->id,
            'type' => 'school_fee',
            'amount' => 150000,
            'paid_amount' => 150000,
            'status' => 'paid',
        ]);
    }

    public function test_store_rejects_submission_when_session_registration_is_disabled(): void
    {
        // Admin turned off registration for the session
        $this->session->update(['registration_enabled' => false]);

        $response = $this->actingAs($this->user)
            ->from(route('student.courses.create'))
            ->post(route('student.courses.store'), [
                'courses' => [$this->courseSem1->id],
            ]);

        $response->assertRedirect(route('student.courses.create'));
        $response->assertSessionHas('error', 'Course registration is currently closed for this academic session.');

        $this->assertDatabaseMissing('course_registrations', [
            'student_id' => $this->student->id,
            'course_id' => $this->courseSem1->id,
        ]);
    }

    public function test_store_rejects_submission_when_semester_registration_ended(): void
    {
        // First semester deadline passed yesterday
        $this->firstSemester->update([
            'registration_ends_at' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('student.courses.create'))
            ->post(route('student.courses.store'), [
                'courses' => [$this->courseSem1->id],
            ]);

        $response->assertRedirect(route('student.courses.create'));
        $response->assertSessionHas('error', 'First Semester registration is closed and cannot be modified.');

        $this->assertDatabaseMissing('course_registrations', [
            'student_id' => $this->student->id,
            'course_id' => $this->courseSem1->id,
        ]);
    }

    public function test_store_permits_submission_on_the_exact_closing_day_before_midnight(): void
    {
        // Registration closing date is TODAY
        $this->firstSemester->update([
            'registration_ends_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('student.courses.store'), [
                'courses' => [$this->courseSem1->id],
            ]);

        $response->assertRedirect(route('student.courses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $this->student->id,
            'course_id' => $this->courseSem1->id,
        ]);
    }

    public function test_store_rejects_second_semester_courses_for_partial_fee_payment(): void
    {
        // Partial fee paid
        $this->invoice->update([
            'amount' => 150000,
            'paid_amount' => 75000,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('student.courses.create'))
            ->post(route('student.courses.store'), [
                'courses' => [$this->courseSem1->id, $this->courseSem2->id],
            ]);

        $response->assertRedirect(route('student.courses.create'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('partial school fee payment', session('error'));

        $this->assertDatabaseMissing('course_registrations', [
            'student_id' => $this->student->id,
            'course_id' => $this->courseSem2->id,
        ]);
    }

    public function test_store_successfully_registers_courses_when_registration_is_open(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('student.courses.store'), [
                'courses' => [$this->courseSem1->id, $this->courseSem2->id],
            ]);

        $response->assertRedirect(route('student.courses.index'));
        $response->assertSessionHas('success', 'Course registration updated successfully.');

        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $this->student->id,
            'course_id' => $this->courseSem1->id,
        ]);
        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $this->student->id,
            'course_id' => $this->courseSem2->id,
        ]);
    }
}
