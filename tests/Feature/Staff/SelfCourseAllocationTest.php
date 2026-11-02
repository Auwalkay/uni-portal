<?php

use App\Mail\CourseAllocationApprovedMail;
use App\Mail\CourseAllocationRejectedMail;
use App\Mail\CourseAllocationRequestedMail;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseAllocationRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function setupSelfAllocationTestData()
{
    // Permissions
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('view_results');
    Permission::findOrCreate('request_course_allocation');
    Permission::findOrCreate('approve_course_allocations');
    Permission::findOrCreate('assign_coordinators');
    Permission::findOrCreate('manage_courses');
    Permission::findOrCreate('view_department_courses');
    Permission::findOrCreate('view_faculty_courses');

    $adminRole = Role::findOrCreate('admin');
    $adminRole->givePermissionTo(['access_admin_dashboard', 'manage_courses', 'approve_course_allocations', 'assign_coordinators']);

    $hodRole = Role::findOrCreate('hod');
    $hodRole->givePermissionTo(['access_admin_dashboard', 'approve_course_allocations', 'assign_coordinators', 'view_department_courses']);

    $deanRole = Role::findOrCreate('dean');
    $deanRole->givePermissionTo(['access_admin_dashboard', 'approve_course_allocations', 'view_faculty_courses']);

    $lecturerRole = Role::findOrCreate('lecturer');
    $lecturerRole->givePermissionTo(['access_admin_dashboard', 'view_results', 'request_course_allocation']);

    // Admin
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_' . uniqid() . '@uni.edu',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole($adminRole);

    // Faculty & Departments
    $faculty = Faculty::create([
        'name' => 'Faculty of Computing',
        'code' => 'FOC',
        'is_active' => true,
    ]);

    $departmentA = Department::create([
        'name' => 'Computer Science',
        'code' => 'CSC',
        'faculty_id' => $faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    $departmentB = Department::create([
        'name' => 'Software Engineering',
        'code' => 'SEN',
        'faculty_id' => $faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    $programmeA = Programme::create([
        'name' => 'B.Sc Computer Science',
        'department_id' => $departmentA->id,
        'is_active' => true,
    ]);

    // HOD
    $hodUser = User::create([
        'name' => 'Prof. HOD',
        'email' => 'hod_' . uniqid() . '@uni.edu',
        'password' => bcrypt('password'),
    ]);
    $hodUser->assignRole($hodRole);
    $hodStaff = Staff::create([
        'user_id' => $hodUser->id,
        'department_id' => $departmentA->id,
        'staff_number' => 'STF-HOD-' . uniqid(),
    ]);

    // Dean
    $deanUser = User::create([
        'name' => 'Prof. Dean',
        'email' => 'dean_' . uniqid() . '@uni.edu',
        'password' => bcrypt('password'),
    ]);
    $deanUser->assignRole($deanRole);
    $deanStaff = Staff::create([
        'user_id' => $deanUser->id,
        'department_id' => $departmentA->id,
        'staff_number' => 'STF-DEAN-' . uniqid(),
    ]);

    // Lecturer A
    $lecturerAUser = User::create([
        'name' => 'Dr. Lecturer A',
        'email' => 'lecturera_' . uniqid() . '@uni.edu',
        'password' => bcrypt('password'),
    ]);
    $lecturerAUser->assignRole($lecturerRole);
    $lecturerAStaff = Staff::create([
        'user_id' => $lecturerAUser->id,
        'department_id' => $departmentA->id,
        'staff_number' => 'STF-LA-' . uniqid(),
    ]);

    // Lecturer B
    $lecturerBUser = User::create([
        'name' => 'Dr. Lecturer B',
        'email' => 'lecturerb_' . uniqid() . '@uni.edu',
        'password' => bcrypt('password'),
    ]);
    $lecturerBUser->assignRole($lecturerRole);
    $lecturerBStaff = Staff::create([
        'user_id' => $lecturerBUser->id,
        'department_id' => $departmentA->id,
        'staff_number' => 'STF-LB-' . uniqid(),
    ]);

    // Active Session
    $session = Session::create([
        'name' => '2026/2027',
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'is_current' => true,
        'registration_enabled' => true,
    ]);

    $semester = Semester::create([
        'session_id' => $session->id,
        'name' => 'First Semester',
        'is_current' => true,
    ]);

    // Courses in Dept A
    $courseA1 = Course::create([
        'code' => 'CSC 101',
        'title' => 'Intro to Computer Science',
        'units' => 3,
        'department_id' => $departmentA->id,
        'programme_id' => $programmeA->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    $courseA2 = Course::create([
        'code' => 'CSC 201',
        'title' => 'Data Structures',
        'units' => 4,
        'department_id' => $departmentA->id,
        'programme_id' => $programmeA->id,
        'level' => 200,
        'semester' => '1',
        'is_active' => true,
    ]);

    // Course in Dept B
    $courseB1 = Course::create([
        'code' => 'SEN 101',
        'title' => 'Intro to Software Engineering',
        'units' => 3,
        'department_id' => $departmentB->id,
        'level' => 100,
        'semester' => '1',
        'is_active' => true,
    ]);

    return compact(
        'admin', 'faculty', 'departmentA', 'departmentB', 'hodUser', 'hodStaff',
        'deanUser', 'deanStaff', 'lecturerAUser', 'lecturerAStaff', 'lecturerBUser',
        'lecturerBStaff', 'session', 'semester', 'courseA1', 'courseA2', 'courseB1'
    );
}

test('lecturer can view self allocation page with department courses and workload stats', function () {
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');

    $response = $this->actingAs($data['lecturerAUser'])
        ->get(route('admin.teaching.courses.self_allocation'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Course/Allocation')
        ->has('courses.data')
        ->where('isOpen', true)
        ->has('stats')
    );
});

test('lecturer cannot submit course request if self allocation window is closed', function () {
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '0');

    $response = $this->actingAs($data['lecturerAUser'])
        ->post(route('admin.teaching.courses.self_allocation.store'), [
            'course_id' => $data['courseA1']->id,
        ]);

    $response->assertSessionHas('error', 'Self course allocation is currently closed by the administration.');
    expect(CourseAllocationRequest::count())->toBe(0);
});

test('lecturer can request a course from another department (cross-department allocation)', function () {
    Mail::fake();
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');

    // Lecturer A is from Dept A, courseB1 belongs to Dept B
    $response = $this->actingAs($data['lecturerAUser'])
        ->post(route('admin.teaching.courses.self_allocation.store'), [
            'course_id' => $data['courseB1']->id,
            'notes' => 'Cross-department request',
        ]);

    $response->assertSessionHas('success', 'Course request submitted successfully and sent for review.');

    $req = CourseAllocationRequest::where('course_id', $data['courseB1']->id)
        ->where('staff_id', $data['lecturerAStaff']->id)
        ->first();

    expect($req)->not->toBeNull();
    expect($req->status)->toBe('pending');
    expect($req->department_id)->toBe($data['departmentB']->id);
    Mail::assertQueued(CourseAllocationRequestedMail::class);
});

test('lecturer self allocation page includes pagination links and departments list', function () {
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');

    $response = $this->actingAs($data['lecturerAUser'])
        ->get(route('admin.teaching.courses.self_allocation'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Course/Allocation')
        ->has('courses.data')
        ->has('courses.links')
        ->has('departments')
        ->where('isOpen', true)
        ->has('stats')
    );
});

test('lecturer can filter courses by department', function () {
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');

    $response = $this->actingAs($data['lecturerAUser'])
        ->get(route('admin.teaching.courses.self_allocation', ['department_id' => $data['departmentB']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Staff/Course/Allocation')
        ->where('courses.data.0.code', 'SEN 101')
    );
});

test('lecturer can request available department course and notification emails are queued', function () {
    Mail::fake();
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');

    $response = $this->actingAs($data['lecturerAUser'])
        ->post(route('admin.teaching.courses.self_allocation.store'), [
            'course_id' => $data['courseA1']->id,
            'notes' => 'I have 5 years experience teaching this course.',
        ]);

    $response->assertSessionHas('success');

    $req = CourseAllocationRequest::where('course_id', $data['courseA1']->id)
        ->where('staff_id', $data['lecturerAStaff']->id)
        ->first();

    expect($req)->not->toBeNull();
    expect($req->status)->toBe('pending');
    expect($req->notes)->toBe('I have 5 years experience teaching this course.');

    // Assert email sent to HOD, Dean, and Admins
    Mail::assertQueued(CourseAllocationRequestedMail::class);
});

test('exclusive rule: another lecturer cannot request an already requested course', function () {
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');

    // Lecturer A requests Course A1
    CourseAllocationRequest::create([
        'staff_id' => $data['lecturerAStaff']->id,
        'course_id' => $data['courseA1']->id,
        'session_id' => $data['session']->id,
        'department_id' => $data['departmentA']->id,
        'status' => 'pending',
    ]);

    // Lecturer B attempts to request the same Course A1
    $response = $this->actingAs($data['lecturerBUser'])
        ->post(route('admin.teaching.courses.self_allocation.store'), [
            'course_id' => $data['courseA1']->id,
        ]);

    $response->assertSessionHas('error', 'Another lecturer has already requested this course.');
    expect(CourseAllocationRequest::where('staff_id', $data['lecturerBStaff']->id)->count())->toBe(0);
});

test('workload limits: request is rejected if exceeding max units allowed', function () {
    $data = setupSelfAllocationTestData();
    SystemSetting::set('enable_lecturer_self_course_allocation', '1');
    SystemSetting::set('lecturer_self_allocation_max_units', '5'); // Limit max to 5 units

    // Allocate 3 units
    CourseAllocation::create([
        'course_id' => $data['courseA1']->id, // 3 units
        'staff_id' => $data['lecturerAStaff']->id,
        'session_id' => $data['session']->id,
        'department_id' => $data['departmentA']->id,
    ]);

    // Attempt to request courseA2 (4 units) -> Total would be 7 > 5
    $response = $this->actingAs($data['lecturerAUser'])
        ->post(route('admin.teaching.courses.self_allocation.store'), [
            'course_id' => $data['courseA2']->id,
        ]);

    $response->assertSessionHas('error');
    expect(CourseAllocationRequest::where('staff_id', $data['lecturerAStaff']->id)->count())->toBe(0);
});

test('lecturer can cancel their pending request', function () {
    $data = setupSelfAllocationTestData();

    $req = CourseAllocationRequest::create([
        'staff_id' => $data['lecturerAStaff']->id,
        'course_id' => $data['courseA1']->id,
        'session_id' => $data['session']->id,
        'department_id' => $data['departmentA']->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($data['lecturerAUser'])
        ->delete(route('admin.teaching.courses.self_allocation.cancel', $req->id));

    $response->assertSessionHas('success');
    expect($req->fresh()->status)->toBe('cancelled');
});

test('HOD can approve a request from their department, creating CourseAllocation and emailing lecturer', function () {
    Mail::fake();
    $data = setupSelfAllocationTestData();

    $req = CourseAllocationRequest::create([
        'staff_id' => $data['lecturerAStaff']->id,
        'course_id' => $data['courseA1']->id,
        'session_id' => $data['session']->id,
        'department_id' => $data['departmentA']->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($data['hodUser'])
        ->post(route('admin.course-allocations.requests.approve', $req->id));

    $response->assertSessionHas('success');

    expect($req->fresh()->status)->toBe('approved');
    expect($req->fresh()->actioned_by)->toBe($data['hodUser']->id);

    // Verify CourseAllocation record created
    $allocation = CourseAllocation::where('course_id', $data['courseA1']->id)
        ->where('staff_id', $data['lecturerAStaff']->id)
        ->where('session_id', $data['session']->id)
        ->first();

    expect($allocation)->not->toBeNull();

    // Verify confirmation email to lecturer
    Mail::assertQueued(CourseAllocationApprovedMail::class, function ($mail) use ($data) {
        return $mail->hasTo($data['lecturerAUser']->email);
    });
});

test('HOD cannot approve request from another department', function () {
    $data = setupSelfAllocationTestData();

    // Request in Dept B
    $reqB = CourseAllocationRequest::create([
        'staff_id' => $data['lecturerAStaff']->id,
        'course_id' => $data['courseB1']->id,
        'session_id' => $data['session']->id,
        'department_id' => $data['departmentB']->id, // Dept B
        'status' => 'pending',
    ]);

    // HOD of Dept A tries to approve
    $response = $this->actingAs($data['hodUser'])
        ->post(route('admin.course-allocations.requests.approve', $reqB->id));

    $response->assertForbidden();
    expect($reqB->fresh()->status)->toBe('pending');
});

test('HOD or Admin can decline request with reason, emailing lecturer', function () {
    Mail::fake();
    $data = setupSelfAllocationTestData();

    $req = CourseAllocationRequest::create([
        'staff_id' => $data['lecturerAStaff']->id,
        'course_id' => $data['courseA1']->id,
        'session_id' => $data['session']->id,
        'department_id' => $data['departmentA']->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($data['hodUser'])
        ->post(route('admin.course-allocations.requests.reject', $req->id), [
            'reason' => 'Workload rebalancing for this semester.',
        ]);

    $response->assertSessionHas('success');

    expect($req->fresh()->status)->toBe('rejected');
    expect($req->fresh()->rejection_reason)->toBe('Workload rebalancing for this semester.');

    Mail::assertQueued(CourseAllocationRejectedMail::class, function ($mail) use ($data) {
        return $mail->hasTo($data['lecturerAUser']->email);
    });
});
