<?php

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('admin course registration index returns active session metrics and student registration details', function () {
    // 1. Setup Admin
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('manage_student_registrations');
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);
    $admin->givePermissionTo(['access_admin_dashboard', 'manage_student_registrations']);

    // 2. Setup Academic Session & Semester
    $session = Session::create([
        'name' => '2026/2027',
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'is_current' => true,
    ]);

    $semester = Semester::create([
        'session_id' => $session->id,
        'name' => 'First Semester',
        'is_current' => true,
    ]);

    // 3. Setup Faculty, Department & Programme
    $faculty = Faculty::create([
        'name' => 'Faculty of Engineering',
        'code' => 'ENG',
        'is_active' => true,
    ]);

    $dept = Department::create([
        'name' => 'Software Engineering',
        'code' => 'SWE',
        'faculty_id' => $faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    $prog = Programme::create([
        'name' => 'B.Eng Software Engineering',
        'department_id' => $dept->id,
        'is_active' => true,
    ]);

    // 4. Setup Course
    $course = Course::create([
        'code' => 'SWE 201',
        'title' => 'Data Structures',
        'units' => 3,
        'department_id' => $dept->id,
        'programme_id' => $prog->id,
        'level' => 200,
        'semester' => '1',
        'is_active' => true,
    ]);

    // 5. Setup Students: Student A (registered) & Student B (unregistered)
    $userA = User::create(['name' => 'Alice Registered', 'email' => 'alice@portal.com', 'password' => bcrypt('password')]);
    $studentA = Student::create([
        'user_id' => $userA->id,
        'faculty_id' => $faculty->id,
        'department_id' => $dept->id,
        'program_id' => $prog->id,
        'current_level' => 200,
        'matriculation_number' => 'ENG/2026/001',
        'status' => 'active',
    ]);

    $studentSessionA = StudentSession::create([
        'student_id' => $studentA->id,
        'session_id' => $session->id,
        'level' => 200,
        'status' => 'active',
    ]);

    CourseRegistration::create([
        'student_id' => $studentA->id,
        'course_id' => $course->id,
        'session_id' => $session->id,
        'semester_id' => $semester->id,
        'student_session_id' => $studentSessionA->id,
    ]);

    $userB = User::create(['name' => 'Bob Pending', 'email' => 'bob@portal.com', 'password' => bcrypt('password')]);
    $studentB = Student::create([
        'user_id' => $userB->id,
        'faculty_id' => $faculty->id,
        'department_id' => $dept->id,
        'program_id' => $prog->id,
        'current_level' => 200,
        'matriculation_number' => 'ENG/2026/002',
        'status' => 'active',
    ]);

    // 6. Request Admin Index
    $response = $this->actingAs($admin)->get(route('admin.course_registration.index'));

    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($session, $studentA, $studentB) {
        $page->component('Admin/Courses/RegistrationManager');

        $props = $page->toArray()['props'];

        // Overview stats
        expect($props['overview_stats']['active_session_name'])->toBe($session->name);
        expect($props['overview_stats']['total_students'])->toBe(2);
        expect($props['overview_stats']['total_registered'])->toBe(1);
        expect($props['overview_stats']['total_pending'])->toBe(1);
        expect($props['overview_stats']['registration_percentage'])->toEqual(50);

        // Student listings
        $data = collect($props['students']['data']);
        $alice = $data->firstWhere('id', $studentA->id);
        $bob = $data->firstWhere('id', $studentB->id);

        expect($alice['is_registered'])->toBeTrue();
        expect($alice['registered_courses_count'])->toBe(1);
        expect($alice['registered_units'])->toBe(3);

        expect($bob['is_registered'])->toBeFalse();
        expect($bob['registered_courses_count'])->toBe(0);
    });
});

test('admin course registration filters by status and level correctly', function () {
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('manage_student_registrations');
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_filter_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);
    $admin->givePermissionTo(['access_admin_dashboard', 'manage_student_registrations']);

    $session = Session::create(['name' => '2026/2027', 'start_date' => now(), 'end_date' => now()->addYear(), 'is_current' => true]);
    $semester = Semester::create(['session_id' => $session->id, 'name' => 'First Semester', 'is_current' => true]);

    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
    $dept = Department::create(['name' => 'Physics', 'code' => 'PHY', 'faculty_id' => $faculty->id, 'is_academic' => true, 'is_active' => true]);

    $user1 = User::create(['name' => 'Physics 100', 'email' => 'p100@portal.com', 'password' => bcrypt('password')]);
    $student1 = Student::create([
        'user_id' => $user1->id,
        'faculty_id' => $faculty->id,
        'department_id' => $dept->id,
        'current_level' => 100,
        'matriculation_number' => 'SCI/100',
        'status' => 'active',
    ]);

    $user2 = User::create(['name' => 'Physics 300', 'email' => 'p300@portal.com', 'password' => bcrypt('password')]);
    $student2 = Student::create([
        'user_id' => $user2->id,
        'faculty_id' => $faculty->id,
        'department_id' => $dept->id,
        'current_level' => 300,
        'matriculation_number' => 'SCI/300',
        'status' => 'active',
    ]);

    $course = Course::create([
        'code' => 'PHY 101', 'title' => 'Mechanics', 'units' => 3, 'department_id' => $dept->id,
        'level' => 100, 'semester' => '1', 'is_active' => true,
    ]);

    CourseRegistration::create([
        'student_id' => $student1->id,
        'course_id' => $course->id,
        'session_id' => $session->id,
        'semester_id' => $semester->id,
    ]);

    // Test filter status = registered
    $response = $this->actingAs($admin)->get(route('admin.course_registration.index', ['status' => 'registered']));
    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($student1, $student2) {
        $ids = collect($page->toArray()['props']['students']['data'])->pluck('id');
        expect($ids)->toContain($student1->id);
        expect($ids)->not->toContain($student2->id);
    });

    // Test filter level = 300
    $responseLevel = $this->actingAs($admin)->get(route('admin.course_registration.index', ['level' => '300']));
    $responseLevel->assertStatus(200);
    $responseLevel->assertInertia(function ($page) use ($student1, $student2) {
        $ids = collect($page->toArray()['props']['students']['data'])->pluck('id');
        expect($ids)->toContain($student2->id);
        expect($ids)->not->toContain($student1->id);
    });
});

test('admin can export course registration roster as csv', function () {
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('manage_student_registrations');
    $admin = User::create([
        'name' => 'Admin Export User',
        'email' => 'admin_exp_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);
    $admin->givePermissionTo(['access_admin_dashboard', 'manage_student_registrations']);

    $session = Session::create(['name' => '2026/2027', 'start_date' => now(), 'end_date' => now()->addYear(), 'is_current' => true]);

    $user = User::create(['name' => 'Export Student', 'email' => 'export@portal.com', 'password' => bcrypt('password')]);
    $student = Student::create([
        'user_id' => $user->id,
        'current_level' => 400,
        'matriculation_number' => 'EXP/400/01',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.course_registration.export'));

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

test('course registration strictly excludes inactive courses from registration lists and counts', function () {
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('manage_student_registrations');
    $admin = User::create([
        'name' => 'Admin Course Test User',
        'email' => 'admin_crs_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);
    $admin->givePermissionTo(['access_admin_dashboard', 'manage_student_registrations']);

    $session = Session::create(['name' => '2026/2027', 'start_date' => now(), 'end_date' => now()->addYear(), 'is_current' => true]);
    $semester = Semester::create(['session_id' => $session->id, 'name' => 'First Semester', 'is_current' => true]);

    $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
    $dept = Department::create(['name' => 'Chemistry', 'code' => 'CHM', 'faculty_id' => $faculty->id, 'is_academic' => true, 'is_active' => true]);

    $user = User::create(['name' => 'Chemistry Student', 'email' => 'chm@portal.com', 'password' => bcrypt('password')]);
    $student = Student::create([
        'user_id' => $user->id,
        'faculty_id' => $faculty->id,
        'department_id' => $dept->id,
        'current_level' => 100,
        'matriculation_number' => 'CHM/100/01',
        'status' => 'active',
    ]);

    $activeCourse = Course::create([
        'code' => 'CHM 101', 'title' => 'General Chemistry I', 'units' => 3, 'department_id' => $dept->id,
        'level' => 100, 'semester' => '1', 'is_active' => true,
    ]);

    $inactiveCourse = Course::create([
        'code' => 'CHM 199', 'title' => 'Discontinued Chemistry', 'units' => 2, 'department_id' => $dept->id,
        'level' => 100, 'semester' => '1', 'is_active' => false,
    ]);

    // Student has both registered
    CourseRegistration::create([
        'student_id' => $student->id,
        'course_id' => $activeCourse->id,
        'session_id' => $session->id,
        'semester_id' => $semester->id,
    ]);
    CourseRegistration::create([
        'student_id' => $student->id,
        'course_id' => $inactiveCourse->id,
        'session_id' => $session->id,
        'semester_id' => $semester->id,
    ]);

    // Clear stats cache
    \Illuminate\Support\Facades\Cache::forget("admin_crs_reg_stats_{$session->id}");

    $response = $this->actingAs($admin)->get(route('admin.course_registration.index'));
    $response->assertStatus(200);

    $response->assertInertia(function ($page) use ($student, $activeCourse, $inactiveCourse) {
        $props = $page->toArray()['props'];
        $studentData = collect($props['students']['data'])->firstWhere('id', $student->id);

        // Registered course count should ONLY count active courses (1, not 2)
        expect($studentData['registered_courses_count'])->toBe(1);
        expect($studentData['registered_units'])->toBe(3);

        $courseCodes = collect($studentData['registered_courses'])->pluck('code');
        expect($courseCodes)->toContain($activeCourse->code);
        expect($courseCodes)->not->toContain($inactiveCourse->code);
    });
});

