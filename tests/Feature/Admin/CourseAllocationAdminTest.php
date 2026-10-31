<?php

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseAllocationRequest;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Role;
use App\Models\Session;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->session = Session::create([
        'name' => '2026/2027',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonths(8),
        'is_current' => true,
    ]);

    $this->faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
    $this->department = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $this->faculty->id]);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');

    $this->lecturerUser = User::factory()->create(['name' => 'Dr. Alan Turing']);
    $this->lecturerUser->assignRole('lecturer');
    $this->lecturerStaff = Staff::create([
        'user_id' => $this->lecturerUser->id,
        'staff_number' => 'STF/TEST/001',
        'department_id' => $this->department->id,
        'is_academic' => true,
    ]);

    $this->course = Course::create([
        'code' => 'CSC 101',
        'title' => 'Intro to Programming',
        'units' => 3,
        'level' => 100,
        'semester' => '1',
        'department_id' => $this->department->id,
    ]);
});

test('admin can view course allocations index with stats and eager loaded relations', function () {
    // Create an allocation
    CourseAllocation::create([
        'course_id' => $this->course->id,
        'staff_id' => $this->lecturerStaff->id,
        'session_id' => $this->session->id,
    ]);

    // Create a pending request
    CourseAllocationRequest::create([
        'staff_id' => $this->lecturerStaff->id,
        'course_id' => $this->course->id,
        'department_id' => $this->department->id,
        'session_id' => $this->session->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('admin.course-allocations.index'));

    $response->assertOk();
    $response->assertInertia(fn($page) => $page
        ->component('Admin/CourseAllocation/Index')
        ->has('allocations.data', 1)
        ->where('allocations.data.0.course.department.name', 'Computer Science')
        ->where('stats.total_allocations', 1)
        ->where('stats.unique_courses', 1)
        ->where('stats.unique_lecturers', 1)
        ->where('stats.pending_requests', 1)
    );
});

test('admin course allocation requests route exists and is accessible', function () {
    $response = $this->actingAs($this->adminUser)->get(route('admin.course-allocations.requests.index'));
    $response->assertOk();
});
