<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Session;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseRosterScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_allocation_defaults_to_assigned_and_can_switch_to_managed(): void
    {
        $accessPerm = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $viewResultsPerm = Permission::firstOrCreate(['name' => 'view_results', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$accessPerm, $viewResultsPerm]);

        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);

        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $myCourse = Course::create([
            'code' => 'CSC 101',
            'title' => 'Intro to CS',
            'units' => 3,
            'semester' => '1',
            'level' => 100,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        Course::create([
            'code' => 'CSC 102',
            'title' => 'Data Structures',
            'units' => 3,
            'semester' => '1',
            'level' => 100,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($adminRole);
        $staff = Staff::create([
            'user_id' => $user->id,
            'staff_number' => 'STF-001',
            'department_id' => $department->id,
            'is_academic' => true,
        ]);

        CourseAllocation::create([
            'course_id' => $myCourse->id,
            'staff_id' => $staff->id,
            'session_id' => $session->id,
        ]);

        // Default view should show ONLY the admin's 1 allocated course
        $defaultResponse = $this->actingAs($user)->get(route('admin.teaching.courses.index'));
        $defaultResponse->assertStatus(200);
        $defaultResponse->assertInertia(fn ($page) => $page
            ->component('Staff/Course/Index')
            ->where('userRole.scope', 'assigned')
            ->where('userRole.canViewOtherCourses', true)
            ->has('courses.data', 1)
            ->where('courses.data.0.code', 'CSC 101')
        );

        // Switching to managed should show all 2 university courses
        $managedResponse = $this->actingAs($user)->get(route('admin.teaching.courses.index', ['scope' => 'managed']));
        $managedResponse->assertStatus(200);
        $managedResponse->assertInertia(fn ($page) => $page
            ->where('userRole.scope', 'managed')
            ->has('courses.data', 2)
        );
    }

    public function test_lecturer_without_elevated_permission_only_sees_assigned_courses(): void
    {
        $accessPerm = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $viewResultsPerm = Permission::firstOrCreate(['name' => 'view_results', 'guard_name' => 'web']);
        $lecturerRole = Role::firstOrCreate(['name' => 'lecturer', 'guard_name' => 'web']);
        $lecturerRole->givePermissionTo([$accessPerm, $viewResultsPerm]);

        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);

        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $myCourse = Course::create([
            'code' => 'CSC 201',
            'title' => 'Algorithms',
            'units' => 3,
            'semester' => '1',
            'level' => 200,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
        Course::create([
            'code' => 'CSC 202',
            'title' => 'Operating Systems',
            'units' => 3,
            'semester' => '1',
            'level' => 200,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($lecturerRole);
        $staff = Staff::create([
            'user_id' => $user->id,
            'staff_number' => 'STF-002',
            'department_id' => $department->id,
            'is_academic' => true,
        ]);

        CourseAllocation::create([
            'course_id' => $myCourse->id,
            'staff_id' => $staff->id,
            'session_id' => $session->id,
        ]);

        // Attempting to request scope=managed is forced back to assigned
        $response = $this->actingAs($user)->get(route('admin.teaching.courses.index', ['scope' => 'managed']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('userRole.scope', 'assigned')
            ->where('userRole.canViewOtherCourses', false)
            ->has('courses.data', 1)
            ->where('courses.data.0.code', 'CSC 201')
        );
    }
}
