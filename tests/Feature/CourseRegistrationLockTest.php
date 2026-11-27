<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
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
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseRegistrationLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_blocks_when_all_semesters_locked_and_only_updates_open_semester(): void
    {
        $portalPerm = Permission::firstOrCreate(['name' => 'access_student_portal', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $studentRole->givePermissionTo([$portalPerm]);

        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $department = Department::create([
            'name' => 'Accounting',
            'code' => 'ACC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);
        $programme = Programme::create([
            'name' => 'B.Sc Accounting',
            'department_id' => $department->id,
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'max_credit_units' => 24,
            'is_active' => true,
        ]);

        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'registration_enabled' => true,
            'is_current' => true,
        ]);

        $firstSem = Semester::create([
            'session_id' => $session->id,
            'name' => 'First Semester',
            'is_current' => false,
            'registration_starts_at' => now()->subDays(30)->toDateString(),
            'registration_ends_at' => now()->subDays(5)->toDateString(),
        ]);

        $secondSem = Semester::create([
            'session_id' => $session->id,
            'name' => 'Second Semester',
            'is_current' => true,
            'registration_starts_at' => now()->subDays(2)->toDateString(),
            'registration_ends_at' => now()->addDays(10)->toDateString(),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($studentRole);

        $student = Student::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'faculty_id' => $faculty->id,
            'program_id' => $programme->id,
            'matriculation_number' => 'MIUACC001',
            'current_level' => 400,
            'gender' => 'male',
            'passport_photo_path' => 'passports/test.jpg',
            'indigene_letter_path' => 'indigene/test.jpg',
            'status' => 'active',
        ]);
        \Illuminate\Support\Facades\Cache::put("student_profile_completed_{$student->id}", true, 3600);
        $student->oLevelResults()->create([
            'exam_type' => 'WAEC',
            'exam_year' => '2022',
            'exam_number' => '12345678AB',
            'subjects' => [],
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'REF-ACC-1',
            'invoice_number' => 'INV-ACC-1',
            'amount' => 400000,
            'paid_amount' => 400000,
            'status' => 'paid',
            'due_date' => now()->addDays(30),
        ]);

        $course1 = Course::create([
            'code' => 'ACC 421',
            'title' => 'Auditing',
            'units' => 3,
            'semester' => '1',
            'level' => 400,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $course2 = Course::create([
            'code' => 'ACC 422',
            'title' => 'Taxation II',
            'units' => 3,
            'semester' => '2',
            'level' => 400,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $existingFirstReg = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course1->id,
            'session_id' => $session->id,
            'semester_id' => $firstSem->id,
        ]);

        // 1. First Semester is locked, Second Semester is open -> only Second Semester updates, First Sem row ID preserved!
        $res = $this->actingAs($user)->post(route('student.courses.store'), [
            'courses' => [$course1->id, $course2->id],
        ]);
        $res->assertRedirect(route('student.courses.index'));

        $this->assertDatabaseHas('course_registrations', [
            'id' => $existingFirstReg->id,
            'course_id' => $course1->id,
            'semester_id' => $firstSem->id,
        ]);
        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $course2->id,
            'semester_id' => $secondSem->id,
        ]);

        // 2. Lock Second Semester as well -> all semesters locked -> store is rejected
        $secondSem->update(['registration_ends_at' => now()->subDay()->toDateString()]);

        $lockedRes = $this->actingAs($user)->post(route('student.courses.store'), [
            'courses' => [$course1->id],
        ]);
        $lockedRes->assertSessionHas('error', 'Course registration is currently closed for all semesters.');
        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $course2->id,
        ]);
    }
}
