<?php

use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createStaffCourseTestData()
{
    Permission::findOrCreate('access_admin_dashboard');
    Permission::findOrCreate('view_results');
    Permission::findOrCreate('manage_courses');

    $adminRole = Role::findOrCreate('admin');
    $adminRole->givePermissionTo(['access_admin_dashboard', 'view_results', 'manage_courses']);

    // Admin user
    $admin = User::create([
        'name' => 'Portal Admin',
        'email' => 'admin_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $admin->assignRole($adminRole);

    // Allocated Staff user
    $lecturerUser = User::create([
        'name' => 'Allocated Lecturer',
        'email' => 'lecturer_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $lecturerUser->givePermissionTo(['access_admin_dashboard', 'view_results']);

    $faculty = Faculty::create([
        'name' => 'Faculty of Science',
        'code' => 'SCI',
        'is_active' => true,
    ]);

    $department = Department::create([
        'name' => 'Computer Science',
        'code' => 'CSC',
        'faculty_id' => $faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    $programme = Programme::create([
        'name' => 'B.Sc Computer Science',
        'department_id' => $department->id,
        'is_active' => true,
    ]);

    $staff = Staff::create([
        'user_id' => $lecturerUser->id,
        'department_id' => $department->id,
        'staff_number' => 'STF-' . uniqid(),
    ]);

    // Unallocated Staff user (has dashboard and view_results, but no course allocation)
    $unallocatedUser = User::create([
        'name' => 'Unallocated Lecturer',
        'email' => 'unallocated_' . uniqid() . '@portal.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $unallocatedUser->givePermissionTo(['access_admin_dashboard', 'view_results']);

    $unallocatedStaff = Staff::create([
        'user_id' => $unallocatedUser->id,
        'department_id' => $department->id,
        'staff_number' => 'STF-' . uniqid(),
    ]);

    // Academic Session & Semester
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

    // Course
    $course = Course::create([
        'code' => 'CSC 201',
        'title' => 'Data Structures and Algorithms',
        'units' => 3,
        'department_id' => $department->id,
        'programme_id' => $programme->id,
        'level' => 200,
        'semester' => '1',
        'is_active' => true,
    ]);

    // Allocate lecturer to Course
    CourseAllocation::create([
        'course_id' => $course->id,
        'staff_id' => $staff->id,
        'session_id' => $session->id,
        'department_id' => $department->id,
        'program_id' => $programme->id,
    ]);

    // Create 3 active Students
    $students = [];
    for ($i = 1; $i <= 3; $i++) {
        $stUser = User::create([
            'name' => "Student {$i}",
            'email' => "student_{$i}_" . uniqid() . "@portal.com",
            'password' => bcrypt('password'),
        ]);

        $matric = "MAT/2026/00{$i}";
        $student = Student::create([
            'user_id' => $stUser->id,
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'program_id' => $programme->id,
            'level' => '200',
            'matriculation_number' => $matric,
        ]);
        $students[] = $student;
    }

    return compact(
        'admin',
        'lecturerUser',
        'staff',
        'unallocatedUser',
        'unallocatedStaff',
        'faculty',
        'department',
        'programme',
        'session',
        'semester',
        'course',
        'students'
    );
}

test('unallocated staff without permissions cannot access course student upload endpoints', function () {
    $data = createStaffCourseTestData();

    // 1. Template
    $response = $this->actingAs($data['unallocatedUser'])
        ->get(route('admin.teaching.courses.students.template', $data['course']->id));
    $response->assertStatus(403);

    // 2. Upload
    $file = UploadedFile::fake()->createWithContent('students.csv', "matriculation_number\nMAT/2026/001");
    $uploadResponse = $this->actingAs($data['unallocatedUser'])
        ->post(route('admin.teaching.courses.students.upload', $data['course']->id), [
            'file' => $file,
        ]);
    $uploadResponse->assertStatus(403);

    // 3. Export
    $exportResponse = $this->actingAs($data['unallocatedUser'])
        ->get(route('admin.teaching.courses.students.export', $data['course']->id));
    $exportResponse->assertStatus(403);
});

test('allocated staff can download student upload template', function () {
    $data = createStaffCourseTestData();

    $response = $this->actingAs($data['lecturerUser'])
        ->get(route('admin.teaching.courses.students.template', $data['course']->id));

    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'attachment; filename=student_upload_template_CSC 201.csv');

    $content = $response->streamedContent();
    expect($content)->toContain('matriculation_number');
    expect($content)->toContain('student_name');
    expect($content)->toContain('MAT/2026/001');
});

test('allocated staff can upload csv to enroll students into the course', function () {
    $data = createStaffCourseTestData();

    $csvContent = "matriculation_number,student_name\n" .
        "{$data['students'][0]->matriculation_number},Student 1\n" .
        "{$data['students'][1]->matriculation_number},Student 2\n";

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $response = $this->actingAs($data['lecturerUser'])
        ->post(route('admin.teaching.courses.students.upload', $data['course']->id), [
            'file' => $file,
            'skip_existing' => true,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $summary = session('import_summary');
    expect($summary)->not->toBeNull();
    expect($summary['enrolled'])->toBe(2);
    expect($summary['skipped'])->toBe(0);
    expect($summary['errors'])->toBeEmpty();

    // Verify CourseRegistration records created in DB
    expect(CourseRegistration::where('course_id', $data['course']->id)->count())->toBe(2);

    $reg1 = CourseRegistration::where('course_id', $data['course']->id)
        ->where('student_id', $data['students'][0]->id)
        ->first();

    expect($reg1)->not->toBeNull();
    expect($reg1->session_id)->toBe($data['session']->id);
    expect($reg1->student_session_id)->not->toBeNull();
});

test('re-uploading existing students skips duplicates', function () {
    $data = createStaffCourseTestData();

    $csvContent = "matriculation_number,student_name\n" .
        "{$data['students'][0]->matriculation_number},Student 1\n" .
        "{$data['students'][1]->matriculation_number},Student 2\n";

    $file1 = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    // First upload: 2 enrolled
    $this->actingAs($data['lecturerUser'])
        ->post(route('admin.teaching.courses.students.upload', $data['course']->id), [
            'file' => $file1,
            'skip_existing' => true,
        ]);

    expect(CourseRegistration::where('course_id', $data['course']->id)->count())->toBe(2);

    // Second upload with 1 existing and 1 new student
    $csvContent2 = "matriculation_number,student_name\n" .
        "{$data['students'][0]->matriculation_number},Student 1\n" .
        "{$data['students'][2]->matriculation_number},Student 3\n";

    $file2 = UploadedFile::fake()->createWithContent('students2.csv', $csvContent2);

    $this->actingAs($data['lecturerUser'])
        ->post(route('admin.teaching.courses.students.upload', $data['course']->id), [
            'file' => $file2,
            'skip_existing' => true,
        ]);

    $summary = session('import_summary');
    expect($summary['enrolled'])->toBe(1);
    expect($summary['skipped'])->toBe(1);

    expect(CourseRegistration::where('course_id', $data['course']->id)->count())->toBe(3);
});

test('uploading unrecognized matriculation numbers logs row errors without crashing', function () {
    $data = createStaffCourseTestData();

    $csvContent = "matriculation_number,student_name\n" .
        "NONEXISTENT/999,Unknown Guy\n" .
        "{$data['students'][0]->matriculation_number},Valid Student\n";

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $response = $this->actingAs($data['lecturerUser'])
        ->post(route('admin.teaching.courses.students.upload', $data['course']->id), [
            'file' => $file,
            'skip_existing' => true,
        ]);

    $response->assertSessionHasNoErrors();
    $summary = session('import_summary');

    expect($summary['enrolled'])->toBe(1);
    expect($summary['skipped'])->toBe(1);
    expect($summary['errors'])->toHaveCount(1);
    expect($summary['errors'][0]['matric'])->toBe('NONEXISTENT/999');
    expect($summary['errors'][0]['row'])->toBe(2);
});

test('admin can upload students and export registered student list', function () {
    $data = createStaffCourseTestData();

    // Admin uploads
    $csvContent = "matric_number\n{$data['students'][0]->matriculation_number}\n";
    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $this->actingAs($data['admin'])
        ->post(route('admin.teaching.courses.students.upload', $data['course']->id), [
            'file' => $file,
        ]);

    expect(CourseRegistration::where('course_id', $data['course']->id)->count())->toBe(1);

    // Admin exports list
    $exportResponse = $this->actingAs($data['admin'])
        ->get(route('admin.teaching.courses.students.export', $data['course']->id));

    $exportResponse->assertStatus(200);
    $content = $exportResponse->streamedContent();

    expect($content)->toContain('Matric Number');
    expect($content)->toContain('Student Name');
    expect($content)->toContain($data['students'][0]->matriculation_number);
});
