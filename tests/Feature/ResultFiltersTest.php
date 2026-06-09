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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResultFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session;
    protected $semester1;
    protected $semester2;
    protected $deptA;
    protected $deptB;
    protected $course1;
    protected $course2;

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
        $this->deptA = Department::create(['name' => 'Chemistry', 'code' => 'CHM', 'faculty_id' => $faculty->id]);
        $this->deptB = Department::create(['name' => 'Biology', 'code' => 'BIO', 'faculty_id' => $faculty->id]);

        $prog1 = Programme::create(['name' => 'Chemistry', 'type' => 'UG', 'department_id' => $this->deptA->id]);
        $prog2 = Programme::create(['name' => 'Biology', 'type' => 'UG', 'department_id' => $this->deptB->id]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'matric_number' => 'MAT123',
            'current_level' => 100,
            'programme_id' => $prog1->id,
            'department_id' => $this->deptA->id,
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

        // Course 1 (Dept A, 100 Level, First Semester)
        $this->course1 = Course::create([
            'code' => 'CHM 101',
            'title' => 'Intro to Chemistry',
            'units' => 3,
            'department_id' => $this->deptA->id,
            'level' => 100,
            'semester' => '1',
        ]);

        // Course 2 (Dept B, 200 Level, Second Semester)
        $this->course2 = Course::create([
            'code' => 'BIO 201',
            'title' => 'Cell Biology',
            'units' => 3,
            'department_id' => $this->deptB->id,
            'level' => 200,
            'semester' => '2',
        ]);

        CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester1->id,
            'score' => 80,
            'grade' => 'A',
            'grade_point' => 5.00,
            'is_published' => true,
        ]);

        CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $this->course2->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester2->id,
            'score' => 70,
            'grade' => 'A',
            'grade_point' => 5.00,
            'is_published' => false,
        ]);
    }

    public function test_admin_can_filter_by_semester()
    {
        $this->actingAs($this->admin);

        // Filter by Semester 1
        $response = $this->get(route('admin.results.index', [
            'session_id' => $this->session->id,
            'semester_id' => $this->semester1->id,
        ]));

        $response->assertStatus(200);
        $courses = $response->viewData('page')['props']['courses']['data'];
        
        $this->assertCount(1, $courses);
        $this->assertEquals('CHM 101', $courses[0]['code']);
    }

    public function test_admin_can_filter_by_publish_status()
    {
        $this->actingAs($this->admin);

        // Filter Published Only
        $response = $this->get(route('admin.results.index', [
            'session_id' => $this->session->id,
            'publish_status' => 'published',
        ]));
        $response->assertStatus(200);
        $courses = $response->viewData('page')['props']['courses']['data'];
        
        $this->assertCount(1, $courses);
        $this->assertEquals('CHM 101', $courses[0]['code']);

        // Filter Unpublished Only
        $response2 = $this->get(route('admin.results.index', [
            'session_id' => $this->session->id,
            'publish_status' => 'unpublished',
        ]));
        $response2->assertStatus(200);
        $courses2 = $response2->viewData('page')['props']['courses']['data'];
        
        $this->assertCount(1, $courses2);
        $this->assertEquals('BIO 201', $courses2[0]['code']);
    }

    public function test_admin_can_sort_results()
    {
        $this->actingAs($this->admin);

        // Sort by Department Ascending (Chemistry then Biology - wait: Chemistry starts with C, Biology starts with B. So B comes before C)
        $response = $this->get(route('admin.results.index', [
            'session_id' => $this->session->id,
            'sort_by' => 'department',
            'sort_dir' => 'asc',
        ]));
        $response->assertStatus(200);
        $courses = $response->viewData('page')['props']['courses']['data'];
        $this->assertEquals('BIO 201', $courses[0]['code']); // Biology first
        $this->assertEquals('CHM 101', $courses[1]['code']); // Chemistry second

        // Sort by Level Descending (200 then 100)
        $response2 = $this->get(route('admin.results.index', [
            'session_id' => $this->session->id,
            'sort_by' => 'level',
            'sort_dir' => 'desc',
        ]));
        $response2->assertStatus(200);
        $courses2 = $response2->viewData('page')['props']['courses']['data'];
        $this->assertEquals('BIO 201', $courses2[0]['code']); // 200 Level first
        $this->assertEquals('CHM 101', $courses2[1]['code']); // 100 Level second
    }
}
