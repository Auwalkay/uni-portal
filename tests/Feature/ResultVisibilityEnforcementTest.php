<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\HostelBooking;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResultVisibilityEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected $studentUser;
    protected $student;
    protected $session;
    protected $semester1;
    protected $semester2;
    protected $course1;
    protected $course2;
    protected $reg1;
    protected $reg2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->studentUser = User::create([
            'name' => 'Test Student',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->studentUser->assignRole('student');

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create(['name' => 'Computer Science', 'type' => 'UG', 'department_id' => $dept->id]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'matric_number' => 'MAT123',
            'current_level' => 100,
            'programme_id' => $prog->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
        ]);

        $this->session = Session::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-01',
            'is_current' => true,
        ]);

        $this->semester1 = Semester::create([
            'name' => 'First Semester',
            'session_id' => $this->session->id,
            'is_current' => true,
        ]);

        $this->semester2 = Semester::create([
            'name' => 'Second Semester',
            'session_id' => $this->session->id,
            'is_current' => false,
        ]);

        $this->course1 = Course::create([
            'code' => 'CSC 101',
            'title' => 'Intro to CS',
            'units' => 3,
            'department_id' => $dept->id,
            'level' => 100,
            'semester' => '1',
        ]);

        $this->course2 = Course::create([
            'code' => 'CSC 102',
            'title' => 'Programming',
            'units' => 4,
            'department_id' => $dept->id,
            'level' => 100,
            'semester' => '2',
        ]);

        $this->reg1 = CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester1->id,
            'score' => 80,
            'grade' => 'A',
            'grade_point' => 5.00,
            'is_published' => true,
        ]);

        $this->reg2 = CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester2->id,
            'score' => 60,
            'grade' => 'B',
            'grade_point' => 4.00,
            'is_published' => true,
        ]);
    }

    public function test_results_visible_when_no_enforcement_is_configured()
    {
        SystemSetting::set('enforce_school_fee_for_results', 'false');
        SystemSetting::set('enforce_hostel_fee_for_results', 'false');

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.results.index'));
        $response->assertStatus(200);

        $history = $response->viewData('page')['props']['history'];
        $this->assertCount(1, $history);
        
        $semesters = $history[0]['semesters'];
        $this->assertCount(2, $semesters);

        // First Semester results should be visible
        $sem1 = collect($semesters)->firstWhere('name', 'First Semester');
        $this->assertFalse($sem1['is_blocked']);
        $this->assertEquals(80, $sem1['courses'][0]['score']);
        $this->assertEquals('A', $sem1['courses'][0]['grade']);

        // Second Semester results should be visible
        $sem2 = collect($semesters)->firstWhere('name', 'Second Semester');
        $this->assertFalse($sem2['is_blocked']);
        $this->assertEquals(60, $sem2['courses'][0]['score']);
        $this->assertEquals('B', $sem2['courses'][0]['grade']);

        // CGPA should calculate correctly using both courses: (3*5 + 4*4)/7 = 4.43
        $cgpa = $response->viewData('page')['props']['cgpa'];
        $this->assertEquals(4.428571428571429, $cgpa);

        // Dashboard CGPA should also be 4.43
        $dashResponse = $this->get(route('student.dashboard'));
        $dashResponse->assertStatus(200);
        $this->assertEquals('4.43', $dashResponse->viewData('page')['props']['stats']['cgpa']);
    }

    public function test_second_semester_blocked_when_school_fee_enforcement_active_and_unpaid()
    {
        SystemSetting::set('enforce_school_fee_for_results', 'true');
        SystemSetting::set('enforce_hostel_fee_for_results', 'false');

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.results.index'));
        $response->assertStatus(200);

        $history = $response->viewData('page')['props']['history'];
        $semesters = $history[0]['semesters'];

        // First Semester results MUST be visible
        $sem1 = collect($semesters)->firstWhere('name', 'First Semester');
        $this->assertFalse($sem1['is_blocked']);
        $this->assertEquals(80, $sem1['courses'][0]['score']);

        // Second Semester results MUST be locked/masked
        $sem2 = collect($semesters)->firstWhere('name', 'Second Semester');
        $this->assertTrue($sem2['is_blocked']);
        $this->assertNull($sem2['courses'][0]['score']);
        $this->assertEquals('Locked', $sem2['courses'][0]['grade']);
        $this->assertNull($sem2['courses'][0]['grade_point']);
        $this->assertEquals(0, $sem2['gpa']);

        // CGPA should exclude second semester results (should be 5.00 based on First Semester only)
        $cgpa = $response->viewData('page')['props']['cgpa'];
        $this->assertEquals(5.00, $cgpa);

        // Dashboard CGPA should also exclude the second semester and show 5.00
        $dashResponse = $this->get(route('student.dashboard'));
        $dashResponse->assertStatus(200);
        $this->assertEquals('5.00', $dashResponse->viewData('page')['props']['stats']['cgpa']);
    }

    public function test_second_semester_visible_when_school_fee_paid()
    {
        SystemSetting::set('enforce_school_fee_for_results', 'true');
        SystemSetting::set('enforce_hostel_fee_for_results', 'false');

        // Create a paid school fee invoice for this student user and session
        Invoice::create([
            'user_id' => $this->studentUser->id,
            'type' => 'school_fee',
            'session_id' => $this->session->id,
            'amount' => 100000,
            'paid_amount' => 100000,
            'status' => 'paid',
        ]);

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.results.index'));
        $response->assertStatus(200);

        $history = $response->viewData('page')['props']['history'];
        $semesters = $history[0]['semesters'];

        $sem2 = collect($semesters)->firstWhere('name', 'Second Semester');
        $this->assertFalse($sem2['is_blocked']);
        $this->assertEquals(60, $sem2['courses'][0]['score']);
        $this->assertEquals('B', $sem2['courses'][0]['grade']);

        // CGPA should be 4.43 now that Second Semester is visible
        $cgpa = $response->viewData('page')['props']['cgpa'];
        $this->assertEquals(4.428571428571429, $cgpa);

        // Dashboard CGPA should also be 4.43
        $dashResponse = $this->get(route('student.dashboard'));
        $dashResponse->assertStatus(200);
        $this->assertEquals('4.43', $dashResponse->viewData('page')['props']['stats']['cgpa']);
    }

    public function test_second_semester_blocked_when_hostel_fee_enforcement_active_and_unpaid()
    {
        SystemSetting::set('enforce_school_fee_for_results', 'false');
        SystemSetting::set('enforce_hostel_fee_for_results', 'true');

        // Student has a hostel booking in this session
        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'type' => 'hostel_fee',
            'session_id' => $this->session->id,
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        HostelBooking::create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'invoice_id' => $invoice->id,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.results.index'));
        $response->assertStatus(200);

        $history = $response->viewData('page')['props']['history'];
        $semesters = $history[0]['semesters'];

        // First Semester results MUST be visible
        $sem1 = collect($semesters)->firstWhere('name', 'First Semester');
        $this->assertFalse($sem1['is_blocked']);

        // Second Semester results MUST be locked/masked
        $sem2 = collect($semesters)->firstWhere('name', 'Second Semester');
        $this->assertTrue($sem2['is_blocked']);
        $this->assertNull($sem2['courses'][0]['score']);
        $this->assertEquals('Locked', $sem2['courses'][0]['grade']);

        // Dashboard CGPA should also exclude the second semester and show 5.00
        $dashResponse = $this->get(route('student.dashboard'));
        $dashResponse->assertStatus(200);
        $this->assertEquals('5.00', $dashResponse->viewData('page')['props']['stats']['cgpa']);
    }

    public function test_second_semester_visible_when_hostel_fee_paid()
    {
        SystemSetting::set('enforce_school_fee_for_results', 'false');
        SystemSetting::set('enforce_hostel_fee_for_results', 'true');

        $invoice = Invoice::create([
            'user_id' => $this->studentUser->id,
            'type' => 'hostel_fee',
            'session_id' => $this->session->id,
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'paid',
        ]);

        HostelBooking::create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'invoice_id' => $invoice->id,
            'status' => 'paid',
        ]);

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.results.index'));
        $response->assertStatus(200);

        $history = $response->viewData('page')['props']['history'];
        $semesters = $history[0]['semesters'];

        $sem2 = collect($semesters)->firstWhere('name', 'Second Semester');
        $this->assertFalse($sem2['is_blocked']);
        $this->assertEquals(60, $sem2['courses'][0]['score']);

        // Dashboard CGPA should also be 4.43
        $dashResponse = $this->get(route('student.dashboard'));
        $dashResponse->assertStatus(200);
        $this->assertEquals('4.43', $dashResponse->viewData('page')['props']['stats']['cgpa']);
    }
}
