<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResultAbsenteeTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $session;
    protected $semester;
    protected $course1;
    protected $course2;
    protected $reg1;
    protected $reg2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        $studentUser = User::create([
            'name' => 'Student User',
            'email' => 'student@portal.com',
            'password' => Hash::make('password'),
        ]);
        $studentUser->assignRole('student');

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create(['name' => 'Computer Science', 'type' => 'UG', 'department_id' => $dept->id]);

        $this->student = Student::create([
            'user_id' => $studentUser->id,
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

        $this->semester = Semester::create([
            'name' => 'First Semester',
            'session_id' => $this->session->id,
            'is_current' => true,
        ]);

        // Course 1 (3 Units)
        $this->course1 = Course::create([
            'code' => 'CSC 101',
            'title' => 'Introduction to CS',
            'units' => 3,
            'department_id' => $dept->id,
            'level' => 100,
            'semester' => '1',
        ]);

        // Course 2 (2 Units)
        $this->course2 = Course::create([
            'code' => 'CSC 102',
            'title' => 'Programming Basics',
            'units' => 2,
            'department_id' => $dept->id,
            'level' => 100,
            'semester' => '1',
        ]);

        $this->reg1 = CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);

        $this->reg2 = CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);
    }

    public function test_admin_can_mark_student_as_absent()
    {
        $this->actingAs($this->admin);

        // Save Course 1 with normal grades, Course 2 as absent
        $response = $this->post(route('admin.results.update', $this->course1->id), [
            'scores' => [
                [
                    'id' => $this->reg1->id,
                    'ca_score' => 30,
                    'exam_score' => 45,
                    'is_absent' => false,
                ]
            ]
        ]);
        $response->assertSessionHasNoErrors();

        $response2 = $this->post(route('admin.results.update', $this->course2->id), [
            'scores' => [
                [
                    'id' => $this->reg2->id,
                    'ca_score' => 15, // should be ignored when absent is true
                    'exam_score' => 30, // should be ignored when absent is true
                    'is_absent' => true,
                ]
            ]
        ]);
        $response2->assertSessionHasNoErrors();

        $this->reg1->refresh();
        $this->reg2->refresh();

        // Check Course 1
        $this->assertEquals(75, $this->reg1->score);
        $this->assertEquals('A', $this->reg1->grade);
        $this->assertEquals(5.0, $this->reg1->grade_point);
        $this->assertFalse($this->reg1->is_absent);

        // Check Course 2 (Absent)
        $this->assertNull($this->reg2->score);
        $this->assertEquals('ABS', $this->reg2->grade);
        $this->assertEquals(0.00, $this->reg2->grade_point);
        $this->assertTrue($this->reg2->is_absent);
        $this->assertEquals(0, $this->reg2->ca_score);
        $this->assertEquals(0, $this->reg2->exam_score);

        // GPA calculation validation:
        // Course 1: 3 units * 5.0 = 15 points
        // Course 2: 2 units * 0.0 = 0 points
        // Total units = 5
        // GPA = 15 / 5 = 3.00
        $gradingService = app(GradingService::class);
        $gpa = $gradingService->calculateGPA(collect([$this->reg1, $this->reg2]));
        $this->assertEquals(3.00, $gpa);
    }
}
