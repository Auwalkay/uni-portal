<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Services\ExamManagementService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamPassVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Session $session;
    protected Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $faculty = Faculty::create(['name' => 'Faculty of Science', 'code' => 'SCI']);
        $department = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $programme = Programme::create(['name' => 'B.Sc Computer Science', 'type' => 'UG', 'department_id' => $department->id]);

        $this->session = Session::create([
            'name' => '2025/2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
        ]);
        $this->semester = Semester::create(['name' => 'First Semester', 'session_id' => $this->session->id, 'is_current' => true]);

        $user = User::create(['name' => 'Zainab Ahmed', 'email' => 'zainab@student.edu.ng', 'password' => Hash::make('password')]);
        $this->student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'U/25/CS/001',
            'department_id' => $department->id,
            'program_id' => $programme->id,
            'current_level' => '200',
        ]);
    }

    public function test_generates_readable_standard_pass_token(): void
    {
        $token = ExamManagementService::generatePassToken($this->student, $this->session, $this->semester);

        // Should follow EXM-{YEAR}-{SEMESTER}-{CLEAN_MATRIC}-{CHECKSUM}
        // e.g. EXM-2026-S1-U25CS001-XXXX
        $this->assertStringStartsWith('EXM-2026-S1-U25CS001-', $token);
        $this->assertMatchesRegularExpression('/^EXM-\d{4}-S[123]-[A-Z0-9]+-[A-F0-9]{4}$/', $token);
    }

    public function test_verify_student_pass_with_full_token(): void
    {
        $token = ExamManagementService::generatePassToken($this->student, $this->session, $this->semester);
        $service = new ExamManagementService();

        $result = $service->verifyStudentPass($token);

        $this->assertNotNull($result);
        $this->assertEquals($this->student->id, $result['student']['id']);
        $this->assertEquals('U/25/CS/001', $result['student']['matric_number']);
    }

    public function test_verify_student_pass_with_clean_matric_or_url(): void
    {
        $token = ExamManagementService::generatePassToken($this->student, $this->session, $this->semester);
        $service = new ExamManagementService();

        // 1. Scanned full URL
        $url = "https://portal.mewar.edu.ng/admin/exams/verify-pass/{$token}";
        $resultFromUrl = $service->verifyStudentPass($url);
        $this->assertNotNull($resultFromUrl);
        $this->assertEquals($this->student->id, $resultFromUrl['student']['id']);

        // 2. Direct matric with slashes
        $resultFromMatric = $service->verifyStudentPass('U/25/CS/001');
        $this->assertNotNull($resultFromMatric);
        $this->assertEquals($this->student->id, $resultFromMatric['student']['id']);

        // 3. Clean matric without slashes
        $resultFromCleanMatric = $service->verifyStudentPass('U25CS001');
        $this->assertNotNull($resultFromCleanMatric);
        $this->assertEquals($this->student->id, $resultFromCleanMatric['student']['id']);
    }

    public function test_scanner_endpoint_defaults_to_ug_session_and_empty_course_selection(): void
    {
        $admin = User::create(['name' => 'Admin Officer', 'email' => 'admin@mewar.edu.ng', 'password' => Hash::make('password')]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.exams.scanner'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Exams/Scanner')
            ->has('schedules')
            ->has('sessions')
            ->has('semesters')
            ->where('selectedSessionId', $this->session->id)
            ->where('selectedSemesterId', $this->semester->id)
            ->where('activeScheduleId', '')
        );
    }
}
