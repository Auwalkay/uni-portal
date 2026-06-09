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

class ResultPublishSessionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $session;
    protected $semester;
    protected $dept1;
    protected $dept2;
    protected $course1;
    protected $course2;
    protected $course3;
    protected $reg1;
    protected $reg2;
    protected $reg3;

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
        $this->dept1 = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $this->dept2 = Department::create(['name' => 'Mathematics', 'code' => 'MTH', 'faculty_id' => $faculty->id]);

        $prog1 = Programme::create(['name' => 'Computer Science', 'type' => 'UG', 'department_id' => $this->dept1->id]);
        $prog2 = Programme::create(['name' => 'Mathematics', 'type' => 'UG', 'department_id' => $this->dept2->id]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'matric_number' => 'MAT123',
            'current_level' => 100,
            'programme_id' => $prog1->id,
            'department_id' => $this->dept1->id,
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

        // Course 1 (Dept 1, 100 Level)
        $this->course1 = Course::create([
            'code' => 'CSC 101',
            'title' => 'Introduction to CS',
            'units' => 3,
            'department_id' => $this->dept1->id,
            'level' => 100,
            'semester' => '1',
        ]);

        // Course 2 (Dept 1, 200 Level)
        $this->course2 = Course::create([
            'code' => 'CSC 201',
            'title' => 'Data Structures',
            'units' => 3,
            'department_id' => $this->dept1->id,
            'level' => 200,
            'semester' => '1',
        ]);

        // Course 3 (Dept 2, 100 Level)
        $this->course3 = Course::create([
            'code' => 'MTH 101',
            'title' => 'Calculus',
            'units' => 3,
            'department_id' => $this->dept2->id,
            'level' => 100,
            'semester' => '1',
        ]);

        $this->reg1 = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
            'score' => 80,
            'grade' => 'A',
            'grade_point' => 5.00,
            'is_published' => false,
        ]);

        $this->reg2 = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $this->course2->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
            'score' => 65,
            'grade' => 'B',
            'grade_point' => 4.00,
            'is_published' => false,
        ]);

        $this->reg3 = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $this->course3->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
            'score' => null, // ungraded course
            'is_published' => false,
        ]);
    }

    public function test_admin_can_publish_all_session_results_for_graded_courses()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('results.publish-session', $this->session->id), [
            'is_published' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $this->reg1->refresh();
        $this->reg2->refresh();
        $this->reg3->refresh();

        // Graded courses should be published
        $this->assertTrue($this->reg1->is_published);
        $this->assertTrue($this->reg2->is_published);

        // Ungraded course should NOT be published
        $this->assertFalse($this->reg3->is_published);
    }

    public function test_admin_can_publish_session_results_filtered_by_department()
    {
        $this->actingAs($this->admin);

        // Publish session results for Dept 2 only (Mathematics)
        // Note: Course 3 is in Dept 2, but is ungraded (score = null). So it shouldn't get published.
        $response = $this->post(route('results.publish-session', $this->session->id), [
            'is_published' => true,
            'department_id' => $this->dept2->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->reg1->refresh();
        $this->reg2->refresh();
        $this->reg3->refresh();

        $this->assertFalse($this->reg1->is_published);
        $this->assertFalse($this->reg2->is_published);
        $this->assertFalse($this->reg3->is_published);

        // Now, let's grade Course 3 and publish again
        $this->reg3->update(['score' => 50, 'grade' => 'C', 'grade_point' => 3.00]);

        $response2 = $this->post(route('results.publish-session', $this->session->id), [
            'is_published' => true,
            'department_id' => $this->dept2->id,
        ]);

        $response2->assertSessionHasNoErrors();

        $this->reg1->refresh();
        $this->reg2->refresh();
        $this->reg3->refresh();

        $this->assertFalse($this->reg1->is_published);
        $this->assertFalse($this->reg2->is_published);
        $this->assertTrue($this->reg3->is_published);
    }

    public function test_admin_can_publish_session_results_filtered_by_level()
    {
        $this->actingAs($this->admin);

        // Publish session results for 100 level only
        $response = $this->post(route('results.publish-session', $this->session->id), [
            'is_published' => true,
            'level' => '100',
        ]);

        $response->assertSessionHasNoErrors();

        $this->reg1->refresh(); // CSC 101 (100 Level, Graded) -> Should publish
        $this->reg2->refresh(); // CSC 201 (200 Level, Graded) -> Should NOT publish
        $this->reg3->refresh(); // MTH 101 (100 Level, Ungraded) -> Should NOT publish

        $this->assertTrue($this->reg1->is_published);
        $this->assertFalse($this->reg2->is_published);
        $this->assertFalse($this->reg3->is_published);
    }

    public function test_admin_can_unpublish_session_results_filtered()
    {
        $this->actingAs($this->admin);

        // First, mark all as published
        $this->reg1->update(['is_published' => true]);
        $this->reg2->update(['is_published' => true]);
        $this->reg3->update(['is_published' => true]);

        // Unpublish CSC department (Dept 1) 100 level results
        $response = $this->post(route('results.publish-session', $this->session->id), [
            'is_published' => false,
            'department_id' => $this->dept1->id,
            'level' => '100',
        ]);

        $response->assertSessionHasNoErrors();

        $this->reg1->refresh(); // CSC 101 (Dept 1, 100 Level) -> Should unpublish
        $this->reg2->refresh(); // CSC 201 (Dept 1, 200 Level) -> Should NOT unpublish
        $this->reg3->refresh(); // MTH 101 (Dept 2, 100 Level) -> Should NOT unpublish

        $this->assertFalse($this->reg1->is_published);
        $this->assertTrue($this->reg2->is_published);
        $this->assertTrue($this->reg3->is_published);
    }

    public function test_admin_can_publish_session_results_for_absentee_students()
    {
        $this->actingAs($this->admin);

        // Course 3 is currently ungraded (score = null, is_absent = false)
        // Mark it as absent (score = null, is_absent = true)
        $this->reg3->update([
            'score' => null,
            'grade' => 'ABS',
            'grade_point' => 0.00,
            'is_absent' => true,
        ]);

        // Publish session results for Dept 2 only (Mathematics, where Course 3 is)
        $response = $this->post(route('results.publish-session', $this->session->id), [
            'is_published' => true,
            'department_id' => $this->dept2->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->reg1->refresh();
        $this->reg2->refresh();
        $this->reg3->refresh();

        $this->assertFalse($this->reg1->is_published);
        $this->assertFalse($this->reg2->is_published);
        $this->assertTrue($this->reg3->is_published); // Should publish because of is_absent = true
    }
}
