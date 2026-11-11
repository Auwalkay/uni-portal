<?php

use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseAllocationRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function setupHodDeanScopingData()
{
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('view_courses');
    Permission::findOrCreate('view_department_courses');
    Permission::findOrCreate('view_faculty_courses');
    Permission::findOrCreate('view_staff');
    Permission::findOrCreate('view_department_staff');
    Permission::findOrCreate('view_faculty_staff');
    Permission::findOrCreate('view_students');
    Permission::findOrCreate('view_department_students');
    Permission::findOrCreate('view_faculty_students');
    Permission::findOrCreate('approve_course_allocations');
    Permission::findOrCreate('manage_courses');

    $hodRole = Role::findOrCreate('hod');
    $hodRole->givePermissionTo([
        'access_admin_dashboard',
        'view_courses', 'view_department_courses', 'manage_courses',
        'view_staff', 'view_department_staff',
        'view_students', 'view_department_students',
        'approve_course_allocations',
    ]);

    $deanRole = Role::findOrCreate('dean');
    $deanRole->givePermissionTo([
        'access_admin_dashboard',
        'view_courses', 'view_faculty_courses', 'manage_courses',
        'view_staff', 'view_faculty_staff',
        'view_students', 'view_faculty_students',
        'approve_course_allocations',
    ]);

    // Faculty 1 & Faculty 2
    $faculty1 = Faculty::create(['name' => 'Faculty of Science', 'code' => 'SCI', 'is_active' => true]);
    $faculty2 = Faculty::create(['name' => 'Faculty of Arts', 'code' => 'ART', 'is_active' => true]);

    // Departments
    $deptSciA = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty1->id, 'is_academic' => true, 'is_active' => true]);
    $deptSciB = Department::create(['name' => 'Mathematics', 'code' => 'MTH', 'faculty_id' => $faculty1->id, 'is_academic' => true, 'is_active' => true]);
    $deptArt = Department::create(['name' => 'English', 'code' => 'ENG', 'faculty_id' => $faculty2->id, 'is_academic' => true, 'is_active' => true]);

    // HOD of Computer Science (Faculty 1, Dept A)
    $hodUser = User::create(['name' => 'HOD CSC', 'email' => 'hod_csc_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $hodUser->assignRole($hodRole);
    Staff::create(['user_id' => $hodUser->id, 'department_id' => $deptSciA->id, 'staff_number' => 'STF-HOD-1']);

    // Dean of Science (Faculty 1)
    $deanUser = User::create(['name' => 'Dean Science', 'email' => 'dean_sci_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $deanUser->assignRole($deanRole);
    Staff::create(['user_id' => $deanUser->id, 'department_id' => $deptSciA->id, 'staff_number' => 'STF-DEAN-1']);

    // Staff in Dept Sci A
    $staffUserA = User::create(['name' => 'Staff CSC', 'email' => 'staff_csc_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $staffRole = Role::findOrCreate('staff');
    $staffUserA->assignRole($staffRole);
    $staffA = Staff::create(['user_id' => $staffUserA->id, 'department_id' => $deptSciA->id, 'staff_number' => 'STF-CSC-1']);

    // Staff in Dept Sci B (Same faculty, different dept)
    $staffUserB = User::create(['name' => 'Staff MTH', 'email' => 'staff_mth_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $staffUserB->assignRole($staffRole);
    $staffB = Staff::create(['user_id' => $staffUserB->id, 'department_id' => $deptSciB->id, 'staff_number' => 'STF-MTH-1']);

    // Staff in Dept Art (Different faculty)
    $staffUserC = User::create(['name' => 'Staff ENG', 'email' => 'staff_eng_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $staffUserC->assignRole($staffRole);
    $staffC = Staff::create(['user_id' => $staffUserC->id, 'department_id' => $deptArt->id, 'staff_number' => 'STF-ENG-1']);

    // Courses
    $courseSciA = Course::create(['code' => 'CSC 101', 'title' => 'Intro CSC', 'units' => 3, 'department_id' => $deptSciA->id, 'level' => 100, 'semester' => '1', 'is_active' => true]);
    $courseSciB = Course::create(['code' => 'MTH 101', 'title' => 'Intro MTH', 'units' => 3, 'department_id' => $deptSciB->id, 'level' => 100, 'semester' => '1', 'is_active' => true]);
    $courseArt = Course::create(['code' => 'ENG 101', 'title' => 'Intro ENG', 'units' => 3, 'department_id' => $deptArt->id, 'level' => 100, 'semester' => '1', 'is_active' => true]);

    // Students
    $studentUserA = User::create(['name' => 'Student CSC', 'email' => 'std_csc_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $studentA = Student::create(['user_id' => $studentUserA->id, 'department_id' => $deptSciA->id, 'matriculation_number' => 'CSC/2026/001']);

    $studentUserB = User::create(['name' => 'Student MTH', 'email' => 'std_mth_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $studentB = Student::create(['user_id' => $studentUserB->id, 'department_id' => $deptSciB->id, 'matriculation_number' => 'MTH/2026/001']);

    $studentUserC = User::create(['name' => 'Student ENG', 'email' => 'std_eng_' . uniqid() . '@uni.edu', 'password' => bcrypt('password')]);
    $studentC = Student::create(['user_id' => $studentUserC->id, 'department_id' => $deptArt->id, 'matriculation_number' => 'ENG/2026/001']);

    // Academic Session
    $session = Session::create([
        'name' => '2026/2027',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(8),
        'is_current' => true,
    ]);

    // Course Allocation Requests
    // 1. Request for Course Sci A (CSC 101 in Dept Sci A, Faculty 1)
    $reqSciA = CourseAllocationRequest::create([
        'staff_id' => $staffA->id,
        'course_id' => $courseSciA->id,
        'session_id' => $session->id,
        'department_id' => $deptSciA->id,
        'status' => 'pending',
    ]);

    // 2. Request for Course Sci B (MTH 101 in Dept Sci B, Faculty 1)
    $reqSciB = CourseAllocationRequest::create([
        'staff_id' => $staffB->id,
        'course_id' => $courseSciB->id,
        'session_id' => $session->id,
        'department_id' => $deptSciB->id,
        'status' => 'pending',
    ]);

    // 3. Request for Course Art (ENG 101 in Dept Art, Faculty 2)
    $reqArt = CourseAllocationRequest::create([
        'staff_id' => $staffC->id,
        'course_id' => $courseArt->id,
        'session_id' => $session->id,
        'department_id' => $deptArt->id,
        'status' => 'pending',
    ]);

    return compact(
        'hodUser', 'deanUser', 'faculty1', 'faculty2', 'deptSciA', 'deptSciB', 'deptArt',
        'staffA', 'staffB', 'staffC', 'courseSciA', 'courseSciB', 'courseArt',
        'studentA', 'studentB', 'studentC', 'session', 'reqSciA', 'reqSciB', 'reqArt'
    );
}

test('HOD can only see their department courses', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['hodUser'])
        ->get(route('admin.academics.courses'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $courses = collect($page->toArray()['props']['courses']['data']);
        expect($courses->pluck('id'))->toContain($data['courseSciA']->id);
        expect($courses->pluck('id'))->not->toContain($data['courseSciB']->id);
        expect($courses->pluck('id'))->not->toContain($data['courseArt']->id);
    });
});

test('Dean can see all courses in their faculty but not outside faculty', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['deanUser'])
        ->get(route('admin.academics.courses'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $courses = collect($page->toArray()['props']['courses']['data']);
        expect($courses->pluck('id'))->toContain($data['courseSciA']->id);
        expect($courses->pluck('id'))->toContain($data['courseSciB']->id);
        expect($courses->pluck('id'))->not->toContain($data['courseArt']->id);
    });
});

test('HOD can only see staff in their department', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['hodUser'])
        ->get(route('admin.staff.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $staffIds = collect($page->toArray()['props']['staff']['data'])
            ->pluck('staff.id');
        expect($staffIds)->toContain($data['staffA']->id);
        expect($staffIds)->not->toContain($data['staffB']->id);
        expect($staffIds)->not->toContain($data['staffC']->id);
    });
});

test('Dean can see all staff in their faculty but not outside faculty', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['deanUser'])
        ->get(route('admin.staff.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $staffIds = collect($page->toArray()['props']['staff']['data'])
            ->pluck('staff.id');
        expect($staffIds)->toContain($data['staffA']->id);
        expect($staffIds)->toContain($data['staffB']->id);
        expect($staffIds)->not->toContain($data['staffC']->id);
    });
});

test('HOD can only see students in their department', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['hodUser'])
        ->get(route('admin.students.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $studentIds = collect($page->toArray()['props']['students']['data'])
            ->pluck('id');
        expect($studentIds)->toContain($data['studentA']->id);
        expect($studentIds)->not->toContain($data['studentB']->id);
        expect($studentIds)->not->toContain($data['studentC']->id);
    });
});

test('Dean can see all students in their faculty but not outside faculty', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['deanUser'])
        ->get(route('admin.students.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $studentIds = collect($page->toArray()['props']['students']['data'])
            ->pluck('id');
        expect($studentIds)->toContain($data['studentA']->id);
        expect($studentIds)->toContain($data['studentB']->id);
        expect($studentIds)->not->toContain($data['studentC']->id);
    });
});

test('HOD can only see course allocation requests for their department', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['hodUser'])
        ->get(route('admin.course-allocations.requests.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $requestIds = collect($page->toArray()['props']['requests']['data'])->pluck('id');
        expect($requestIds)->toContain($data['reqSciA']->id);
        expect($requestIds)->not->toContain($data['reqSciB']->id);
        expect($requestIds)->not->toContain($data['reqArt']->id);

        $stats = $page->toArray()['props']['stats'];
        expect($stats['pending'])->toBe(1);
        expect($stats['total'])->toBe(1);
        expect($page->toArray()['props']['isHod'])->toBeTrue();
        expect($page->toArray()['props']['isDean'])->toBeFalse();
    });
});

test('Dean can see course allocation requests in their faculty but not outside faculty', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['deanUser'])
        ->get(route('admin.course-allocations.requests.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $requestIds = collect($page->toArray()['props']['requests']['data'])->pluck('id');
        expect($requestIds)->toContain($data['reqSciA']->id);
        expect($requestIds)->toContain($data['reqSciB']->id);
        expect($requestIds)->not->toContain($data['reqArt']->id);

        $stats = $page->toArray()['props']['stats'];
        expect($stats['pending'])->toBe(2);
        expect($stats['total'])->toBe(2);
        expect($page->toArray()['props']['isHod'])->toBeFalse();
        expect($page->toArray()['props']['isDean'])->toBeTrue();
    });
});

test('HOD cannot approve or reject course request outside their department', function () {
    $data = setupHodDeanScopingData();

    // HOD tries to approve request in Dept Sci B -> 403
    $this->actingAs($data['hodUser'])
        ->post(route('admin.course-allocations.requests.approve', $data['reqSciB']->id))
        ->assertForbidden();

    // HOD tries to reject request in Dept Art -> 403
    $this->actingAs($data['hodUser'])
        ->post(route('admin.course-allocations.requests.reject', $data['reqArt']->id), [
            'reason' => 'Invalid',
        ])
        ->assertForbidden();

    // HOD approves request in their own department -> succeeds
    $response = $this->actingAs($data['hodUser'])
        ->post(route('admin.course-allocations.requests.approve', $data['reqSciA']->id));

    $response->assertRedirect();
    expect($data['reqSciA']->fresh()->status)->toBe('approved');
});

test('Dean cannot approve or reject course request outside their faculty', function () {
    $data = setupHodDeanScopingData();

    // Dean of Science tries to approve request in Faculty of Arts (Dept Art) -> 403
    $this->actingAs($data['deanUser'])
        ->post(route('admin.course-allocations.requests.approve', $data['reqArt']->id))
        ->assertForbidden();

    // Dean of Science tries to reject request in Faculty of Arts -> 403
    $this->actingAs($data['deanUser'])
        ->post(route('admin.course-allocations.requests.reject', $data['reqArt']->id), [
            'reason' => 'Invalid faculty',
        ])
        ->assertForbidden();

    // Dean can approve request in their own faculty (Dept Sci B) -> succeeds
    $response = $this->actingAs($data['deanUser'])
        ->post(route('admin.course-allocations.requests.approve', $data['reqSciB']->id));

    $response->assertRedirect();
    expect($data['reqSciB']->fresh()->status)->toBe('approved');
});

test('Dean department filter options are scoped to their faculty', function () {
    $data = setupHodDeanScopingData();

    $response = $this->actingAs($data['deanUser'])
        ->get(route('admin.course-allocations.requests.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) use ($data) {
        $faculties = collect($page->toArray()['props']['faculties']);
        // Only Dean's faculty is present
        expect($faculties->pluck('id'))->toContain($data['faculty1']->id);
        expect($faculties->pluck('id'))->not->toContain($data['faculty2']->id);

        // Departments inside faculty are only Sci A and Sci B
        $deptIds = $faculties->first()['departments'] ?? [];
        $deptIdList = collect($deptIds)->pluck('id');
        expect($deptIdList)->toContain($data['deptSciA']->id);
        expect($deptIdList)->toContain($data['deptSciB']->id);
        expect($deptIdList)->not->toContain($data['deptArt']->id);
    });
});
