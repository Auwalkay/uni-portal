<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentCourseAttendance;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentLectureAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $lecturerUser;
    protected $lecturerStaff;
    protected $studentUser;
    protected $student;
    protected $course;
    protected $session;
    protected $semester;
    protected $registration;

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

        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
        $department = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);

        $this->course = Course::create([
            'code' => 'CSC201',
            'title' => 'Data Structures and Algorithms',
            'units' => 3,
            'semester' => 1,
            'level' => '200',
            'department_id' => $department->id,
        ]);

        // Lecturer
        $this->lecturerUser = User::create([
            'name' => 'Dr. Lecturer',
            'email' => 'lecturer@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->lecturerUser->assignRole('lecturer');
        $this->lecturerStaff = Staff::create([
            'user_id' => $this->lecturerUser->id,
            'staff_number' => 'STF-001',
            'is_academic' => true,
        ]);

        CourseAllocation::create([
            'course_id' => $this->course->id,
            'staff_id' => $this->lecturerStaff->id,
            'session_id' => $this->session->id,
            'department_id' => $department->id,
        ]);

        // Student
        $this->studentUser = User::create([
            'name' => 'Alice Student',
            'email' => 'alice@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->studentUser->assignRole('student');

        $state = \App\Models\State::create(['name' => 'Kano']);
        $lga = \App\Models\Lga::create(['name' => 'Nasarawa', 'state_id' => $state->id]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'matriculation_number' => 'CSC/2026/001',
            'department_id' => $department->id,
            'current_level' => '200',
            'gender' => 'female',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'passport_photo_path' => 'passports/test.jpg',
            'indigene_letter_path' => 'indigene/test.jpg',
            'status' => 'active',
        ]);

        \App\Models\OLevelResult::create([
            'student_id' => $this->student->id,
            'exam_type' => 'WAEC',
            'exam_year' => '2024',
            'exam_number' => '12345678WA',
            'subjects' => json_encode([['subject' => 'Mathematics', 'grade' => 'A1']]),
        ]);

        $this->registration = CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);
    }

    public function test_lecturer_can_download_attendance_template_with_enrolled_students()
    {
        $this->actingAs($this->lecturerUser);

        $response = $this->get(route('admin.teaching.courses.attendance.template', $this->course->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('matriculation_number,student_name,department,status', $content);
        $this->assertStringContainsString('CSC/2026/001', $content);
        $this->assertTrue(stripos($content, 'Alice Student') !== false);
        $this->assertStringContainsString('Present', $content);
    }

    public function test_unallocated_user_cannot_upload_attendance()
    {
        $otherUser = User::create([
            'name' => 'Other Staff',
            'email' => 'other@portal.com',
            'password' => Hash::make('password'),
        ]);
        $otherUser->assignRole('staff');

        $this->actingAs($otherUser);

        $response = $this->post(route('admin.teaching.courses.attendance.upload', $this->course->id), [
            'attendance_date' => '2026-11-15',
            'file' => UploadedFile::fake()->create('attendance.csv', 100),
        ]);

        $response->assertStatus(403);
    }

    public function test_lecturer_can_upload_attendance_csv_for_specific_date()
    {
        $this->actingAs($this->lecturerUser);

        $csvContent = "matriculation_number,student_name,status\nCSC/2026/001,Alice Student,Present\n";
        $file = UploadedFile::fake()->createWithContent('attendance.csv', $csvContent);

        $response = $this->post(route('admin.teaching.courses.attendance.upload', $this->course->id), [
            'attendance_date' => '2026-11-15',
            'file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $attendance = StudentCourseAttendance::where('course_registration_id', $this->registration->id)
            ->where('attendance_date', '2026-11-15')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals(AttendanceStatus::PRESENT, $attendance->status);
        $this->assertEquals($this->student->id, $attendance->student_id);
        $this->assertEquals($this->course->id, $attendance->course_id);
        $this->assertEquals($this->session->id, $attendance->session_id);
    }

    public function test_reuploading_attendance_for_same_date_updates_records_without_duplicates()
    {
        $this->actingAs($this->lecturerUser);

        // First upload: Present
        $csv1 = "matriculation_number,student_name,status\nCSC/2026/001,Alice Student,Present\n";
        $this->post(route('admin.teaching.courses.attendance.upload', $this->course->id), [
            'attendance_date' => '2026-11-15',
            'file' => UploadedFile::fake()->createWithContent('attendance1.csv', $csv1),
        ]);

        $this->assertEquals(1, StudentCourseAttendance::where('course_registration_id', $this->registration->id)->count());
        $this->assertEquals(AttendanceStatus::PRESENT, StudentCourseAttendance::first()->status);

        // Second upload: Absent for same date
        $csv2 = "matriculation_number,student_name,status\nCSC/2026/001,Alice Student,Absent\n";
        $this->post(route('admin.teaching.courses.attendance.upload', $this->course->id), [
            'attendance_date' => '2026-11-15',
            'file' => UploadedFile::fake()->createWithContent('attendance2.csv', $csv2),
        ]);

        // Still exactly 1 record, but updated to absent
        $this->assertEquals(1, StudentCourseAttendance::where('course_registration_id', $this->registration->id)->count());
        $this->assertEquals(AttendanceStatus::ABSENT, StudentCourseAttendance::first()->status);
    }

    public function test_student_can_view_their_attendance_percentage_and_dates()
    {
        // Upload 2 dates: 1 Present, 1 Absent (50% attendance)
        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-10',
            'status' => AttendanceStatus::PRESENT,
        ]);

        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-12',
            'status' => AttendanceStatus::ABSENT,
        ]);

        $this->actingAs($this->studentUser);

        $response = $this->get(route('student.attendance.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Student/Attendance/Index')
            ->has('courses', 1)
            ->where('courses.0.course_code', $this->course->code)
            ->where('courses.0.total_classes', 2)
            ->where('courses.0.attended_classes', 1)
            ->where('courses.0.missed_classes', 1)
            ->where('courses.0.attendance_percentage', 50)
            ->where('stats.overall_percentage', 50)
            ->where('filters.session_id', $this->session->id)
            ->where('filters.semester_id', $this->semester->id)
        );
    }

    public function test_student_attendance_view_filters_by_session_and_semester()
    {
        // Create a Second Semester in the same session
        $secondSemester = Semester::create([
            'session_id' => $this->session->id,
            'name' => 'Second Semester',
            'is_current' => false,
        ]);

        $secondCourse = Course::create([
            'code' => 'CSC202',
            'title' => 'Operating Systems',
            'units' => 3,
            'semester' => 2,
            'level' => '200',
            'department_id' => $this->course->department_id,
        ]);

        $reg2 = CourseRegistration::create([
            'student_id' => $this->student->id,
            'course_id' => $secondCourse->id,
            'session_id' => $this->session->id,
            'semester_id' => $secondSemester->id,
        ]);

        // Attendance for 2nd course in 2nd semester
        StudentCourseAttendance::create([
            'course_registration_id' => $reg2->id,
            'student_id' => $this->student->id,
            'course_id' => $secondCourse->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-15',
            'status' => AttendanceStatus::PRESENT,
        ]);

        $this->actingAs($this->studentUser);

        // 1. By default, only active semester (First Semester) courses show up
        $response1 = $this->get(route('student.attendance.index'));
        $response1->assertInertia(fn ($page) => $page
            ->has('courses', 1)
            ->where('courses.0.course_code', $this->course->code)
            ->where('filters.semester_id', $this->semester->id)
        );

        // 2. Filter by Second Semester -> only second course CSC202 shows up
        $response2 = $this->get(route('student.attendance.index', [
            'session_id' => $this->session->id,
            'semester_id' => $secondSemester->id,
        ]));
        $response2->assertInertia(fn ($page) => $page
            ->has('courses', 1)
            ->where('courses.0.course_code', $secondCourse->code)
            ->where('courses.0.attendance_percentage', 100)
            ->where('filters.semester_id', $secondSemester->id)
        );

        // 3. Filter by all semesters -> both courses show up
        $responseAll = $this->get(route('student.attendance.index', [
            'session_id' => $this->session->id,
            'semester_id' => 'all',
        ]));
        $responseAll->assertInertia(fn ($page) => $page
            ->has('courses', 2)
            ->where('filters.semester_id', 'all')
        );
    }

    public function test_postgraduate_student_attendance_defaults_to_pg_session_and_semester()
    {
        // Create PG active session and semester
        $pgSession = Session::create([
            'name' => '2026/2027 PG',
            'academic_stream' => 'PG',
            'is_current' => true,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(10),
        ]);

        $pgSemester = Semester::create([
            'session_id' => $pgSession->id,
            'name' => 'First PG Semester',
            'is_current' => true,
        ]);

        // Create PG user and student (level 700)
        $pgUser = User::create([
            'name' => 'Bob PG Student',
            'email' => 'bob.pg@portal.com',
            'password' => Hash::make('password'),
        ]);
        $pgUser->assignRole('student');

        $pgStudent = Student::create([
            'user_id' => $pgUser->id,
            'matriculation_number' => 'PG/CSC/2026/001',
            'department_id' => $this->course->department_id,
            'current_level' => '700',
            'gender' => 'male',
            'state_id' => $this->student->state_id,
            'lga_id' => $this->student->lga_id,
            'passport_photo_path' => 'passports/pg_test.jpg',
            'indigene_letter_path' => 'indigene/pg_test.jpg',
            'status' => 'active',
        ]);

        \App\Models\OLevelResult::create([
            'student_id' => $pgStudent->id,
            'exam_type' => 'WAEC',
            'exam_year' => '2020',
            'exam_number' => '87654321WA',
            'subjects' => json_encode([['subject' => 'Mathematics', 'grade' => 'A1']]),
        ]);

        $this->actingAs($pgUser);

        $response = $this->get(route('student.attendance.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('studentStream', 'PG')
            ->where('filters.session_id', $pgSession->id)
            ->where('filters.semester_id', $pgSemester->id)
        );
    }

    public function test_exam_docket_respects_attendance_enforcement_setting()
    {
        // Setup school fee invoice so fees are cleared
        \App\Models\Invoice::create([
            'user_id' => $this->studentUser->id,
            'session_id' => $this->session->id,
            'reference' => 'SCH-TEST',
            'type' => 'school_fee',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'paid',
        ]);

        // Student has 1 Present, 1 Absent (50% attendance)
        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-10',
            'status' => AttendanceStatus::PRESENT,
        ]);
        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-12',
            'status' => AttendanceStatus::ABSENT,
        ]);

        $this->actingAs($this->studentUser);

        // Case 1: Enforcement OFF -> Cleared
        SystemSetting::set('enforce_attendance_for_exam_docket', '0');
        $response = $this->get(route('student.exam_docket.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('clearance.is_cleared', true)
        );

        // Case 2: Enforcement ON (75% min required, student has 50%) -> Blocked
        SystemSetting::set('enforce_attendance_for_exam_docket', '1');
        SystemSetting::set('attendance_exam_docket_min_percentage', '75');

        $response = $this->get(route('student.exam_docket.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('clearance.is_cleared', false)
            ->where('clearance.attendance_cleared', false)
            ->where('clearance.attendance_rate', 50)
        );

        // Case 3: Student achieves >= 75% attendance -> Cleared
        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-14',
            'status' => AttendanceStatus::PRESENT,
        ]);
        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-16',
            'status' => AttendanceStatus::PRESENT,
        ]);
        // Now 3 Present out of 4 = 75%

        $response = $this->get(route('student.exam_docket.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('clearance.is_cleared', true)
            ->where('clearance.attendance_cleared', true)
            ->where('clearance.attendance_rate', 75)
        );
    }

    public function test_lecturer_can_delete_attendance_date()
    {
        $this->actingAs($this->lecturerUser);

        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-20',
            'status' => AttendanceStatus::PRESENT,
        ]);

        $this->assertEquals(1, StudentCourseAttendance::where('attendance_date', '2026-11-20')->count());

        $response = $this->delete(route('admin.teaching.courses.attendance.delete', [$this->course->id, '2026-11-20']));

        $response->assertSessionHas('success');
        $this->assertEquals(0, StudentCourseAttendance::where('attendance_date', '2026-11-20')->count());
    }

    public function test_lecturer_can_export_attendance_matrix()
    {
        $this->actingAs($this->lecturerUser);

        StudentCourseAttendance::create([
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-20',
            'status' => AttendanceStatus::PRESENT,
        ]);

        $response = $this->get(route('admin.teaching.courses.attendance.export', $this->course->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Matric Number', $content);
        $this->assertStringContainsString('2026-11-20', $content);
        $this->assertStringContainsString('Attendance %', $content);
        $this->assertStringContainsString('CSC/2026/001', $content);
        $this->assertStringContainsString('100%', $content);
    }

    public function test_lecturer_can_download_attendance_template_filtered_by_department()
    {
        $this->actingAs($this->lecturerUser);

        $seDept = Department::create(['name' => 'Software Engineering', 'code' => 'SEN', 'faculty_id' => $this->course->department->faculty_id]);
        $userSe = User::create(['name' => 'Bob SE', 'email' => 'bob@portal.com', 'password' => Hash::make('password')]);
        $userSe->assignRole('student');
        $state = \App\Models\State::first();
        $lga = \App\Models\Lga::first();

        $studentSe = Student::create([
            'user_id' => $userSe->id,
            'matriculation_number' => 'SEN/2026/002',
            'department_id' => $seDept->id,
            'current_level' => '200',
            'gender' => 'male',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'passport_photo_path' => 'passports/test.jpg',
            'indigene_letter_path' => 'indigene/test.jpg',
            'status' => 'active',
        ]);
        \App\Models\OLevelResult::create([
            'student_id' => $studentSe->id,
            'exam_type' => 'WAEC',
            'exam_year' => '2024',
            'exam_number' => '87654321WA',
            'subjects' => json_encode([['subject' => 'Mathematics', 'grade' => 'A1']]),
        ]);
        CourseRegistration::create([
            'student_id' => $studentSe->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);

        // Filter by Computer Science
        $resCs = $this->get(route('admin.teaching.courses.attendance.template', [$this->course->id, 'department_ids' => [$this->course->department_id]]));
        $resCs->assertStatus(200);
        $csContent = $resCs->streamedContent();
        $this->assertStringContainsString('CSC/2026/001', $csContent);
        $this->assertStringNotContainsString('SEN/2026/002', $csContent);

        // Filter by Software Engineering
        $resSe = $this->get(route('admin.teaching.courses.attendance.template', [$this->course->id, 'department_ids' => [$seDept->id]]));
        $resSe->assertStatus(200);
        $seContent = $resSe->streamedContent();
        $this->assertStringContainsString('SEN/2026/002', $seContent);
        $this->assertStringNotContainsString('CSC/2026/001', $seContent);
    }

    public function test_lecturer_can_upload_attendance_for_specific_department_cohorts()
    {
        $this->actingAs($this->lecturerUser);

        $seDept = Department::create(['name' => 'Software Engineering', 'code' => 'SEN', 'faculty_id' => $this->course->department->faculty_id]);

        $csvContent = "matriculation_number,status\nCSC/2026/001,Present\n";
        $file = UploadedFile::fake()->createWithContent('attendance_cs.csv', $csvContent);

        $response = $this->post(route('admin.teaching.courses.attendance.upload', $this->course->id), [
            'attendance_date' => '2026-11-12',
            'department_ids' => [$this->course->department_id],
            'file' => $file,
        ]);

        $response->assertSessionHas('success');

        $sessionRecord = \App\Models\CourseLectureSession::where('course_id', $this->course->id)
            ->whereDate('lecture_date', '2026-11-12')
            ->first();

        $this->assertNotNull($sessionRecord);
        $this->assertEquals([$this->course->department_id], $sessionRecord->department_ids);
        $this->assertContains('Computer Science', $sessionRecord->department_names);

        $attendance = StudentCourseAttendance::where('course_id', $this->course->id)
            ->whereDate('attendance_date', '2026-11-12')
            ->first();
        $this->assertNotNull($attendance);
        $this->assertEquals($sessionRecord->id, $attendance->course_lecture_session_id);
    }

    public function test_student_attendance_calculation_respects_department_cohorts()
    {
        $seDept = Department::create(['name' => 'Software Engineering', 'code' => 'SEN', 'faculty_id' => $this->course->department->faculty_id]);
        $userSe = User::create(['name' => 'Bob SE', 'email' => 'bob@portal.com', 'password' => Hash::make('password')]);
        $userSe->assignRole('student');
        $state = \App\Models\State::first();
        $lga = \App\Models\Lga::first();

        $studentSe = Student::create([
            'user_id' => $userSe->id,
            'matriculation_number' => 'SEN/2026/002',
            'department_id' => $seDept->id,
            'current_level' => '200',
            'gender' => 'male',
            'state_id' => $state->id,
            'lga_id' => $lga->id,
            'passport_photo_path' => 'passports/test.jpg',
            'indigene_letter_path' => 'indigene/test.jpg',
            'status' => 'active',
        ]);
        \App\Models\OLevelResult::create([
            'student_id' => $studentSe->id,
            'exam_type' => 'WAEC',
            'exam_year' => '2024',
            'exam_number' => '87654321WA',
            'subjects' => json_encode([['subject' => 'Mathematics', 'grade' => 'A1']]),
        ]);
        $regSe = CourseRegistration::create([
            'student_id' => $studentSe->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'semester_id' => $this->semester->id,
        ]);

        // Nov 12: Lecture for Computer Science only (Alice attended)
        $sessionNov12 = \App\Models\CourseLectureSession::create([
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'lecture_date' => '2026-11-12',
            'department_ids' => [$this->course->department_id],
            'department_names' => ['Computer Science'],
        ]);
        StudentCourseAttendance::create([
            'course_lecture_session_id' => $sessionNov12->id,
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-12',
            'status' => AttendanceStatus::PRESENT,
        ]);

        // Nov 13: Lecture for Software Engineering only (Bob attended)
        $sessionNov13 = \App\Models\CourseLectureSession::create([
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'lecture_date' => '2026-11-13',
            'department_ids' => [$seDept->id],
            'department_names' => ['Software Engineering'],
        ]);
        StudentCourseAttendance::create([
            'course_lecture_session_id' => $sessionNov13->id,
            'course_registration_id' => $regSe->id,
            'student_id' => $studentSe->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-13',
            'status' => AttendanceStatus::PRESENT,
        ]);

        // Nov 14: Combined / Joint Lecture for all departments (Both attended)
        $sessionNov14 = \App\Models\CourseLectureSession::create([
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'lecture_date' => '2026-11-14',
            'department_ids' => null, // All departments
            'department_names' => null,
        ]);
        StudentCourseAttendance::create([
            'course_lecture_session_id' => $sessionNov14->id,
            'course_registration_id' => $this->registration->id,
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-14',
            'status' => AttendanceStatus::PRESENT,
        ]);
        StudentCourseAttendance::create([
            'course_lecture_session_id' => $sessionNov14->id,
            'course_registration_id' => $regSe->id,
            'student_id' => $studentSe->id,
            'course_id' => $this->course->id,
            'session_id' => $this->session->id,
            'attendance_date' => '2026-11-14',
            'status' => AttendanceStatus::PRESENT,
        ]);

        // Verify Alice (CS): Total held should be 2 (Nov 12 + Nov 14), NOT 3! Attended = 2 (100%).
        $this->actingAs($this->studentUser);
        $resAlice = $this->get(route('student.attendance.index'));
        $resAlice->assertStatus(200);
        $resAlice->assertInertia(fn ($page) => $page
            ->where('stats.total_classes', 2)
            ->where('stats.total_attended', 2)
            ->where('stats.overall_percentage', 100)
            ->where('courses.0.total_classes', 2)
            ->where('courses.0.attended_classes', 2)
            ->where('courses.0.attendance_percentage', 100)
        );

        // Verify Bob (SE): Total held should be 2 (Nov 13 + Nov 14), NOT 3! Attended = 2 (100%).
        $this->actingAs($userSe);
        $resBob = $this->get(route('student.attendance.index'));
        $resBob->assertStatus(200);
        $resBob->assertInertia(fn ($page) => $page
            ->where('stats.total_classes', 2)
            ->where('stats.total_attended', 2)
            ->where('stats.overall_percentage', 100)
            ->where('courses.0.total_classes', 2)
            ->where('courses.0.attended_classes', 2)
            ->where('courses.0.attendance_percentage', 100)
        );
    }
}
