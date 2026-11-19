<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseLectureSession;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentCourseAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminStudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_student_attendance_and_lecturer_date_details(): void
    {
        $accessPerm = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $viewResultsPerm = Permission::firstOrCreate(['name' => 'view_results', 'guard_name' => 'web']);
        $manageCoursesPerm = Permission::firstOrCreate(['name' => 'manage_courses', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$accessPerm, $viewResultsPerm, $manageCoursesPerm]);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole($adminRole);

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

        $semester = Semester::create([
            'session_id' => $session->id,
            'name' => 'First Semester',
            'is_current' => true,
        ]);

        $course = Course::create([
            'code' => 'CSC 101',
            'title' => 'Intro to CS',
            'units' => 3,
            'semester' => '1',
            'level' => 100,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $studentUser1 = User::factory()->create(['name' => 'Alice Student']);
        $student1 = Student::create([
            'user_id' => $studentUser1->id,
            'department_id' => $department->id,
            'faculty_id' => $faculty->id,
            'matriculation_number' => 'MIU260101',
            'current_level' => 100,
            'status' => 'active',
        ]);

        $studentUser2 = User::factory()->create(['name' => 'Bob Student']);
        $student2 = Student::create([
            'user_id' => $studentUser2->id,
            'department_id' => $department->id,
            'faculty_id' => $faculty->id,
            'matriculation_number' => 'MIU260102',
            'current_level' => 100,
            'status' => 'active',
        ]);

        $reg1 = CourseRegistration::create([
            'student_id' => $student1->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'semester_id' => $semester->id,
        ]);

        $reg2 = CourseRegistration::create([
            'student_id' => $student2->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'semester_id' => $semester->id,
        ]);

        // 2 lecture sessions held: Alice attended 2 (100%), Bob attended 1 (50% - below 75%)
        CourseLectureSession::create([
            'course_id' => $course->id,
            'session_id' => $session->id,
            'lecture_date' => '2026-10-01',
        ]);
        CourseLectureSession::create([
            'course_id' => $course->id,
            'session_id' => $session->id,
            'lecture_date' => '2026-10-08',
        ]);

        StudentCourseAttendance::create([
            'course_registration_id' => $reg1->id,
            'student_id' => $student1->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'attendance_date' => '2026-10-01',
            'status' => 'present',
        ]);
        StudentCourseAttendance::create([
            'course_registration_id' => $reg1->id,
            'student_id' => $student1->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'attendance_date' => '2026-10-08',
            'status' => 'present',
        ]);
        StudentCourseAttendance::create([
            'course_registration_id' => $reg2->id,
            'student_id' => $student2->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'attendance_date' => '2026-10-01',
            'status' => 'present',
        ]);
        StudentCourseAttendance::create([
            'course_registration_id' => $reg2->id,
            'student_id' => $student2->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'attendance_date' => '2026-10-08',
            'status' => 'absent',
        ]);

        // 1. Per-date View Details JSON endpoint
        $detailsRes = $this->actingAs($admin)
            ->getJson(route('admin.teaching.courses.attendance.details', [$course->id, '2026-10-08']));
        $detailsRes->assertStatus(200)
            ->assertJsonCount(2, 'records');

        // 2. Super Admin Student Attendance page (all + below threshold)
        $indexRes = $this->actingAs($admin)->get('/admin/student-attendance');
        $indexRes->assertStatus(200);
        $indexRes->assertInertia(fn ($page) => $page
            ->component('Admin/Academic/StudentAttendance')
            ->where('stats.total_sessions_held', 2)
            ->where('stats.total_records', 2)
            ->where('stats.below_threshold_count', 1)
            ->has('records.data', 2)
        );

        $belowRes = $this->actingAs($admin)->get('/admin/student-attendance?threshold=below');
        $belowRes->assertStatus(200);
        $belowRes->assertInertia(fn ($page) => $page
            ->has('records.data', 1)
            ->where('records.data.0.matriculation_number', 'MIU260102')
            ->where('records.data.0.percentage', 50)
        );

        // 3. CSV export
        $exportRes = $this->actingAs($admin)->get('/admin/student-attendance/export');
        $exportRes->assertStatus(200);
    }
}
