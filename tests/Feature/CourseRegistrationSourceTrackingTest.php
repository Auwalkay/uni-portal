<?php

namespace Tests\Feature;

use App\Imports\CourseStudentImport;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourseRegistrationSourceTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected $studentUser;
    protected $student;
    protected $adminUser;
    protected $lecturerUser;
    protected $session;
    protected $semester;
    protected $course;
    protected $department;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('access_student_portal');
        Permission::findOrCreate('access_admin_dashboard');
        Permission::findOrCreate('manage_student_registrations');
        Permission::findOrCreate('manage_courses');

        $this->session = Session::create([
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
            'registration_enabled' => true,
            'school_fee_payment_enabled' => true,
        ]);

        $this->semester = Semester::create([
            'session_id' => $this->session->id,
            'name' => 'First Semester',
            'is_current' => true,
            'registration_starts_at' => now()->subMonth(),
            'registration_ends_at' => now()->addMonth(),
        ]);

        $faculty = Faculty::create([
            'name' => 'Faculty of Science',
            'code' => 'SCI',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);

        $programme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'department_id' => $this->department->id,
            'max_credit_units' => 24,
            'is_active' => true,
        ]);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Student User & Profile
        $this->studentUser = User::create([
            'name' => 'Alice Student',
            'email' => 'alice@portal.edu',
            'password' => Hash::make('password'),
        ]);
        $this->studentUser->givePermissionTo('access_student_portal');

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'faculty_id' => $faculty->id,
            'department_id' => $this->department->id,
            'program_id' => $programme->id,
            'level' => '100',
            'matriculation_number' => 'MAT/CSC/001',
        ]);

        // Full fee payment
        Invoice::create([
            'reference' => 'INV-' . uniqid(),
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'type' => 'school_fee',
            'amount' => 150000,
            'paid_amount' => 150000,
            'status' => 'paid',
        ]);

        // Admin User
        $this->adminUser = User::create([
            'name' => 'Admin Officer',
            'email' => 'admin@portal.edu',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole('admin');

        // Lecturer User
        $this->lecturerUser = User::create([
            'name' => 'Dr. Lecturer',
            'email' => 'lecturer@portal.edu',
            'password' => Hash::make('password'),
        ]);
        $this->lecturerUser->givePermissionTo(['access_admin_dashboard', 'manage_courses']);

        // Course
        $this->course = Course::create([
            'code' => 'CSC 101',
            'title' => 'Intro to Programming',
            'units' => 3,
            'department_id' => $this->department->id,
            'level' => 100,
            'semester' => '1',
            'is_active' => true,
        ]);
    }

    public function test_student_self_registration_records_student_source(): void
    {
        $response = $this->actingAs($this->studentUser)->post(route('student.courses.store'), [
            'courses' => [$this->course->id],
        ]);

        $response->assertRedirect(route('student.courses.index'));

        $registration = CourseRegistration::where('student_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertNotNull($registration);
        $this->assertEquals($this->studentUser->id, $registration->registered_by);
        $this->assertEquals('student', $registration->registration_source);
        $this->assertTrue($registration->isSelfRegistered());
        $this->assertFalse($registration->isAdminRegistered());
    }

    public function test_admin_registration_records_admin_source_and_admin_id(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.course_registration.store', $this->student->id), [
            'courses' => [$this->course->id],
        ]);

        $response->assertRedirect(route('admin.course_registration.manage', $this->student->id));

        $registration = CourseRegistration::where('student_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertNotNull($registration);
        $this->assertEquals($this->adminUser->id, $registration->registered_by);
        $this->assertEquals('admin', $registration->registration_source);
        $this->assertFalse($registration->isSelfRegistered());
        $this->assertTrue($registration->isAdminRegistered());
    }

    public function test_bulk_student_import_records_import_source_and_uploader_id(): void
    {
        $import = new CourseStudentImport(
            $this->course,
            $this->session,
            $this->semester,
            false,
            $this->lecturerUser->id
        );

        $rows = collect([
            [
                'matriculation_number' => $this->student->matriculation_number,
                'student_name' => $this->studentUser->name,
            ]
        ]);

        $import->collection($rows);

        $registration = CourseRegistration::where('student_id', $this->student->id)
            ->where('course_id', $this->course->id)
            ->first();

        $this->assertNotNull($registration);
        $this->assertEquals($this->lecturerUser->id, $registration->registered_by);
        $this->assertEquals('import', $registration->registration_source);
        $this->assertTrue($registration->isImported());
    }

    public function test_course_show_and_export_include_registration_tracking_details(): void
    {
        // Admin registers course for student
        CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
            'registered_by' => $this->adminUser->id,
            'registration_source' => 'admin',
        ]);

        // 1. Check Inertia Show endpoint
        $showResponse = $this->actingAs($this->adminUser)->get(route('admin.teaching.courses.show', $this->course->id));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn($page) => $page
            ->component('Staff/Course/Show')
            ->has('students.0', fn($student) => $student
                ->where('matriculation_number', $this->student->matriculation_number)
                ->where('registration_source', 'admin')
                ->where('registered_by_name', $this->adminUser->name)
                ->where('is_self_registered', false)
                ->etc()
            )
        );

        // 2. Check CSV Export
        $exportResponse = $this->actingAs($this->adminUser)->get(route('admin.teaching.courses.students.export', $this->course->id));
        $exportResponse->assertOk();

        // Capture CSV output
        ob_start();
        $exportResponse->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('Registration Source', $csvContent);
        $this->assertStringContainsString('Registered By', $csvContent);
        $this->assertStringContainsString('Admin Override', $csvContent);
        $this->assertStringContainsString($this->adminUser->name, $csvContent);
    }
}
