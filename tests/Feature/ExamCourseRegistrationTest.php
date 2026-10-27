<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExamCourseRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $examOfficer;
    protected User $normalStudentUser;
    protected Session $session;
    protected Semester $semester;
    protected Course $course1;
    protected Course $course2;
    protected Student $student1;
    protected Student $student2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Academic Structure
        $faculty = Faculty::create(['name' => 'Faculty of Science', 'code' => 'SCI']);
        $department = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $programme = Programme::create(['name' => 'B.Sc Computer Science', 'type' => 'UG', 'department_id' => $department->id]);

        $this->session = Session::create([
            'name' => '2025/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
        ]);
        $this->semester = Semester::create(['name' => 'First Semester', 'session_id' => $this->session->id, 'is_current' => true]);

        // Courses
        $this->course1 = Course::create([
            'code' => 'CSC 201',
            'title' => 'Computer Programming II',
            'units' => 3,
            'department_id' => $department->id,
            'level' => 200,
            'semester' => '1',
        ]);

        $this->course2 = Course::create([
            'code' => 'CSC 203',
            'title' => 'Discrete Structures',
            'units' => 3,
            'department_id' => $department->id,
            'level' => 200,
            'semester' => '1',
        ]);

        // Exam Officer
        $this->examOfficer = User::create([
            'name' => 'Exam Officer Dr. Audu',
            'email' => 'exams@mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $this->examOfficer->assignRole('exams_officer');

        // Students & Registrations
        $user1 = User::create(['name' => 'Zainab Ahmed', 'email' => 'zainab@student.edu.ng', 'password' => Hash::make('password')]);
        $this->student1 = Student::create([
            'user_id' => $user1->id,
            'matriculation_number' => 'U/25/CS/001',
            'department_id' => $department->id,
            'program_id' => $programme->id,
            'current_level' => '200',
        ]);

        $user2 = User::create(['name' => 'Bello Musa', 'email' => 'bello@student.edu.ng', 'password' => Hash::make('password')]);
        $this->student2 = Student::create([
            'user_id' => $user2->id,
            'matriculation_number' => 'U/25/CS/002',
            'department_id' => $department->id,
            'program_id' => $programme->id,
            'current_level' => '200',
        ]);

        $this->normalStudentUser = $user1;

        // Register student 1 for course 1 & 2
        CourseRegistration::create([
            'student_id' => $this->student1->id,
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);
        CourseRegistration::create([
            'student_id' => $this->student1->id,
            'course_id' => $this->course2->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);

        // Register student 2 only for course 1
        CourseRegistration::create([
            'student_id' => $this->student2->id,
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);
    }

    public function test_exam_officer_can_view_course_students_roster()
    {
        $response = $this->actingAs($this->examOfficer)->get(route('admin.exams.courses.students', [
            'course_id' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Exams/CourseStudents')
            ->has('courses')
            ->where('selectedCourse.id', $this->course1->id)
            ->where('stats.total_registered', 2)
            ->has('registrations.data', 2)
        );
    }

    public function test_enrolment_counts_api_returns_accurate_totals_per_session_and_semester()
    {
        $response = $this->actingAs($this->examOfficer)->getJson(route('admin.exams.api.enrolment_counts', [
            'course_ids' => [$this->course1->id, $this->course2->id],
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        // Course 1 has 2 registered students, Course 2 has 1 registered student
        $this->assertEquals(2, $data['counts'][$this->course1->id]);
        $this->assertEquals(1, $data['counts'][$this->course2->id]);
        $this->assertEquals(3, $data['total']);
    }

    public function test_exam_officer_can_export_course_students_roster_as_csv()
    {
        $response = $this->actingAs($this->examOfficer)->get(route('admin.exams.courses.export_students', [
            'course' => $this->course1->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('Exam_Roster_CSC201', $response->headers->get('content-disposition'));
    }

    public function test_exam_exercise_show_includes_registered_students_count_for_slots()
    {
        $exam = Exam::create([
            'reference_id' => 'EXM-TEST-001',
            'title' => '2025/2026 First Semester Examinations',
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
            'created_by' => $this->examOfficer->id,
            'is_published' => false,
            'docket_printing_enabled' => true,
        ]);

        ExamSchedule::create([
            'exam_id' => $exam->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
            'course_id' => $this->course1->id,
            'exam_date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'venue' => 'Main Multipurpose Hall',
            'max_capacity' => 150,
            'reference_id' => 'SCH-001',
        ]);

        $response = $this->actingAs($this->examOfficer)->get(route('admin.exams.exercises.show', $exam->id));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Exams/Show')
            ->where('schedules.0.registered_students_count', 2)
        );
    }

    public function test_unauthorized_user_cannot_access_course_enrolment_views()
    {
        $response = $this->actingAs($this->normalStudentUser)->get(route('admin.exams.courses.students', [
            'course_id' => $this->course1->id,
        ]));

        $response->assertStatus(403);
    }
}
