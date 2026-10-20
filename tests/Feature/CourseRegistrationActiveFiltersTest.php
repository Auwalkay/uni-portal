<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createRegistrationStudent() {
    Permission::findOrCreate('access_student_portal');

    $user = User::create([
        'name' => 'Registration Student ' . uniqid(),
        'email' => 'reg_student_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);

    $user->givePermissionTo('access_student_portal');

    $faculty = Faculty::create([
        'name' => 'Faculty of Science',
        'code' => 'SCI',
        'is_active' => true,
    ]);

    $activeDept = Department::create([
        'name' => 'Computer Science',
        'code' => 'CSC',
        'faculty_id' => $faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    $inactiveDept = Department::create([
        'name' => 'Archived Department',
        'code' => 'ARC',
        'faculty_id' => $faculty->id,
        'is_academic' => true,
        'is_active' => false,
    ]);

    $activeProg = Programme::create([
        'name' => 'B.Sc Computer Science',
        'department_id' => $activeDept->id,
        'is_active' => true,
    ]);

    $inactiveProg = Programme::create([
        'name' => 'Archived Programme',
        'department_id' => $activeDept->id,
        'is_active' => false,
    ]);

    $student = Student::create([
        'user_id' => $user->id,
        'faculty_id' => $faculty->id,
        'department_id' => $activeDept->id,
        'program_id' => $activeProg->id,
        'level' => '100',
        'matriculation_number' => 'MAT/' . uniqid(),
    ]);

    $session = Session::create([
        'name' => '2026/2027',
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'is_current' => true,
        'registration_enabled' => true,
        'school_fee_payment_enabled' => true,
    ]);

    $semester = Semester::create([
        'session_id' => $session->id,
        'name' => 'First Semester',
        'is_current' => true,
    ]);

    // Create School Fee Invoice as paid so registration can open
    Invoice::create([
        'user_id' => $user->id,
        'reference' => 'INV-' . uniqid(),
        'type' => 'school_fee',
        'session_id' => $session->id,
        'amount' => 100000,
        'paid_amount' => 100000,
        'status' => 'paid',
    ]);

    return compact('user', 'student', 'session', 'semester', 'faculty', 'activeDept', 'inactiveDept', 'activeProg', 'inactiveProg');
}

test('course registration page only provides active departments and programs in filters', function () {
    $data = createRegistrationStudent();

    $response = $this->actingAs($data['user'])->get(route('student.courses.create'));

    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($data) {
        $page->component('Student/Courses/Form');

        // Verify departments only include active ones
        $departments = collect($page->toArray()['props']['departments']);
        expect($departments->pluck('id'))->toContain($data['activeDept']->id);
        expect($departments->pluck('id'))->not->toContain($data['inactiveDept']->id);

        // Verify programmes only include active ones
        $programmes = collect($page->toArray()['props']['programmes']);
        expect($programmes->pluck('id'))->toContain($data['activeProg']->id);
        expect($programmes->pluck('id'))->not->toContain($data['inactiveProg']->id);
    });
});

test('course registration page only returns active courses', function () {
    $data = createRegistrationStudent();

    $activeCourse = Course::create([
        'code' => 'CSC 101',
        'title' => 'Introduction to Computing',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'programme_id' => $data['activeProg']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $inactiveCourse = Course::create([
        'code' => 'CSC 109',
        'title' => 'Obsolete Computing Concept',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'programme_id' => $data['activeProg']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => false,
    ]);

    $courseInInactiveDept = Course::create([
        'code' => 'ARC 101',
        'title' => 'Archived History Course',
        'units' => 2,
        'department_id' => $data['inactiveDept']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $response = $this->actingAs($data['user'])->get(route('student.courses.create'));

    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($activeCourse, $inactiveCourse, $courseInInactiveDept) {
        $courses = collect($page->toArray()['props']['courses']);
        expect($courses->pluck('id'))->toContain($activeCourse->id);
        expect($courses->pluck('id'))->not->toContain($inactiveCourse->id);
        expect($courses->pluck('id'))->not->toContain($courseInInactiveDept->id);
    });
});

test('student cannot submit inactive courses during course registration', function () {
    $data = createRegistrationStudent();

    $activeCourse = Course::create([
        'code' => 'CSC 101',
        'title' => 'Intro to Computer Science',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $inactiveCourse = Course::create([
        'code' => 'CSC 199',
        'title' => 'Deprecated Course',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => false,
    ]);

    $response = $this->actingAs($data['user'])->post(route('student.courses.store'), [
        'courses' => [$activeCourse->id, $inactiveCourse->id],
    ]);

    $response->assertSessionHas('error');
    expect(session('error'))->toContain('inactive');
});

test('filtering by programme_id returns only active courses belonging to that programme', function () {
    $data = createRegistrationStudent();

    $prog1Course = Course::create([
        'code' => 'CSC 101',
        'title' => 'Computing 101',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'programme_id' => $data['activeProg']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $otherProg = Programme::create([
        'name' => 'B.Sc Software Engineering',
        'department_id' => $data['activeDept']->id,
        'is_active' => true,
    ]);

    $prog2Course = Course::create([
        'code' => 'SEN 101',
        'title' => 'Software Engineering 101',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'programme_id' => $otherProg->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $response = $this->actingAs($data['user'])->get(route('student.courses.create', [
        'programme_id' => $data['activeProg']->id,
        'department_id' => $data['activeDept']->id,
        'level' => 100,
    ]));

    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($prog1Course, $prog2Course) {
        $courses = collect($page->toArray()['props']['courses']);
        expect($courses->pluck('id'))->toContain($prog1Course->id);
        expect($courses->pluck('id'))->not->toContain($prog2Course->id);
    });
});

test('admin course registration page only returns active departments, programs, and courses', function () {
    $data = createRegistrationStudent();

    $adminUser = User::create([
        'name' => 'Admin User ' . uniqid(),
        'email' => 'admin_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
    ]);
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('manage_student_registrations');
    $adminUser->givePermissionTo(['access_admin_dashboard', 'manage_student_registrations']);

    $activeCourse = Course::create([
        'code' => 'CSC 101',
        'title' => 'Intro to Computer Science',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $inactiveCourse = Course::create([
        'code' => 'CSC 199',
        'title' => 'Deprecated Course',
        'units' => 3,
        'department_id' => $data['activeDept']->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => false,
    ]);

    $response = $this->actingAs($adminUser)->get(route('admin.course_registration.manage', $data['student']->id));

    $response->assertStatus(200);
    $response->assertInertia(function ($page) use ($data, $activeCourse, $inactiveCourse) {
        $props = $page->toArray()['props'];
        $depts = collect($props['departments']);
        $progs = collect($props['programmes']);
        $courses = collect($props['courses']);

        expect($depts->pluck('id'))->toContain($data['activeDept']->id);
        expect($depts->pluck('id'))->not->toContain($data['inactiveDept']->id);

        expect($progs->pluck('id'))->toContain($data['activeProg']->id);
        expect($progs->pluck('id'))->not->toContain($data['inactiveProg']->id);

        expect($courses->pluck('id'))->toContain($activeCourse->id);
        expect($courses->pluck('id'))->not->toContain($inactiveCourse->id);
    });
});
