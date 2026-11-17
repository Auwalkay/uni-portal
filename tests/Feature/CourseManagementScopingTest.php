<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourseManagementScopingTest extends TestCase
{
    use RefreshDatabase;

    protected $session;
    protected $semester;
    protected $facultySci;
    protected $facultyArts;
    protected $deptCsc;
    protected $deptMath;
    protected $deptEng;
    protected $courseCsc;
    protected $courseMath;
    protected $courseEng;

    protected $adminUser;
    protected $deanSciUser;
    protected $hodCscUser;
    protected $lecturerUser;
    protected $unrelatedLecturerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->session = Session::create([
            'name' => '2026/2027',
            'is_current' => true,
            'registration_enabled' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
        ]);

        $this->semester = Semester::create([
            'session_id' => $this->session->id,
            'name' => 'First Semester',
            'is_current' => true,
        ]);

        // Faculties
        $this->facultySci = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
        $this->facultyArts = Faculty::create(['name' => 'Arts & Humanities', 'code' => 'ART']);

        // Departments
        $this->deptCsc = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $this->facultySci->id, 'is_academic' => true]);
        $this->deptMath = Department::create(['name' => 'Mathematics', 'code' => 'MTH', 'faculty_id' => $this->facultySci->id, 'is_academic' => true]);
        $this->deptEng = Department::create(['name' => 'English Studies', 'code' => 'ENG', 'faculty_id' => $this->facultyArts->id, 'is_academic' => true]);

        // Courses
        $this->courseCsc = Course::create([
            'code' => 'CSC101',
            'title' => 'Intro to Computing',
            'units' => 3,
            'semester' => 1,
            'level' => '100',
            'department_id' => $this->deptCsc->id,
        ]);

        $this->courseMath = Course::create([
            'code' => 'MTH101',
            'title' => 'Algebra & Calculus',
            'units' => 3,
            'semester' => 1,
            'level' => '100',
            'department_id' => $this->deptMath->id,
        ]);

        $this->courseEng = Course::create([
            'code' => 'ENG101',
            'title' => 'Basic English',
            'units' => 2,
            'semester' => 1,
            'level' => '100',
            'department_id' => $this->deptEng->id,
        ]);

        // 1. Admin
        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@university.edu',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole('admin');

        // 2. Dean (Science)
        $this->deanSciUser = User::create([
            'name' => 'Prof. Dean Science',
            'email' => 'dean.sci@university.edu',
            'password' => Hash::make('password'),
        ]);
        $this->deanSciUser->assignRole('dean');
        Staff::create([
            'user_id' => $this->deanSciUser->id,
            'staff_number' => 'STF_DEAN_SCI',
            'department_id' => $this->deptCsc->id, // Housed in CSC, which is in Faculty Science
            'designation' => 'Dean',
        ]);

        // 3. HOD (Computer Science)
        $this->hodCscUser = User::create([
            'name' => 'Dr. HOD Computer Science',
            'email' => 'hod.csc@university.edu',
            'password' => Hash::make('password'),
        ]);
        $this->hodCscUser->assignRole('hod');
        Staff::create([
            'user_id' => $this->hodCscUser->id,
            'staff_number' => 'STF_HOD_CSC',
            'department_id' => $this->deptCsc->id,
            'designation' => 'Head of Department',
        ]);

        // 4. Lecturer allocated to CSC101
        $this->lecturerUser = User::create([
            'name' => 'Lecturer Allocated',
            'email' => 'lecturer@university.edu',
            'password' => Hash::make('password'),
        ]);
        $this->lecturerUser->assignRole('lecturer');
        $lecturerStaff = Staff::create([
            'user_id' => $this->lecturerUser->id,
            'staff_number' => 'STF_LECTURER',
            'department_id' => $this->deptCsc->id,
            'designation' => 'Lecturer II',
        ]);
        CourseAllocation::create([
            'course_id' => $this->courseCsc->id,
            'staff_id' => $lecturerStaff->id,
            'session_id' => $this->session->id,
        ]);

        // 5. Unrelated Lecturer (Allocated only to ENG101)
        $this->unrelatedLecturerUser = User::create([
            'name' => 'Lecturer Unrelated',
            'email' => 'unrelated@university.edu',
            'password' => Hash::make('password'),
        ]);
        $this->unrelatedLecturerUser->assignRole('lecturer');
        $unrelatedStaff = Staff::create([
            'user_id' => $this->unrelatedLecturerUser->id,
            'staff_number' => 'STF_UNRELATED',
            'department_id' => $this->deptEng->id,
            'designation' => 'Assistant Lecturer',
        ]);
        CourseAllocation::create([
            'course_id' => $this->courseEng->id,
            'staff_id' => $unrelatedStaff->id,
            'session_id' => $this->session->id,
        ]);
    }

    public function test_admin_can_access_course_index_and_view_all_courses(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.teaching.courses.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Staff/Course/Index')
            ->has('courses.data')
            ->where('userRole.isAdmin', true)
            ->where('userRole.scopeTitle', 'All University Courses')
        );
    }

    public function test_admin_can_view_any_course_roster(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.teaching.courses.show', $this->courseEng->id));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Staff/Course/Show')
            ->where('course.code', 'ENG 101')
        );
    }

    public function test_hod_can_view_department_courses_and_roster(): void
    {
        $response = $this->actingAs($this->hodCscUser)->get(route('admin.teaching.courses.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Staff/Course/Index')
            ->where('userRole.isHod', true)
            ->where('userRole.scopeTitle', 'Computer Science Courses')
        );

        // HOD can view CSC 101 roster
        $showResponse = $this->actingAs($this->hodCscUser)->get(route('admin.teaching.courses.show', $this->courseCsc->id));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn($page) => $page
            ->component('Staff/Course/Show')
            ->where('course.code', 'CSC 101')
        );

        // HOD is denied access to ENG 101 (another department)
        $deniedResponse = $this->actingAs($this->hodCscUser)->get(route('admin.teaching.courses.show', $this->courseEng->id));
        $deniedResponse->assertStatus(403);
    }

    public function test_dean_can_view_faculty_courses_and_is_denied_other_faculty_courses(): void
    {
        $response = $this->actingAs($this->deanSciUser)->get(route('admin.teaching.courses.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Staff/Course/Index')
            ->where('userRole.isDean', true)
            ->where('userRole.scopeTitle', 'Science Courses')
        );

        // Dean can view courses in Science faculty (CSC 101 and MTH 101)
        $cscResponse = $this->actingAs($this->deanSciUser)->get(route('admin.teaching.courses.show', $this->courseCsc->id));
        $cscResponse->assertOk();

        $mthResponse = $this->actingAs($this->deanSciUser)->get(route('admin.teaching.courses.show', $this->courseMath->id));
        $mthResponse->assertOk();

        // Dean is denied access to ENG 101 (Arts & Humanities faculty)
        $deniedResponse = $this->actingAs($this->deanSciUser)->get(route('admin.teaching.courses.show', $this->courseEng->id));
        $deniedResponse->assertStatus(403);
    }

    public function test_allocated_lecturer_can_view_allocated_course_and_denied_unallocated(): void
    {
        // Allocated lecturer can view CSC 101
        $response = $this->actingAs($this->lecturerUser)->get(route('admin.teaching.courses.show', $this->courseCsc->id));
        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Staff/Course/Show')
            ->where('course.code', 'CSC 101')
        );

        // Allocated lecturer cannot view MTH 101 (unallocated)
        $deniedResponse = $this->actingAs($this->lecturerUser)->get(route('admin.teaching.courses.show', $this->courseMath->id));
        $deniedResponse->assertStatus(403);
    }

    public function test_hod_can_export_student_roster_for_department_course(): void
    {
        $response = $this->actingAs($this->hodCscUser)->get(route('admin.teaching.courses.students.export', $this->courseCsc->id));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', strtolower($response->headers->get('Content-Type')));
    }

    public function test_hod_cannot_export_student_roster_for_another_department_course(): void
    {
        $response = $this->actingAs($this->hodCscUser)->get(route('admin.teaching.courses.students.export', $this->courseEng->id));

        $response->assertStatus(403);
    }
}
