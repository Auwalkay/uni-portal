<?php

namespace Tests\Feature;

use App\Helpers\MatriculationNumberHelper;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AcademicCacheService;
use App\Services\EnrollmentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostgraduatePhaseOneArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AcademicCacheService::clearAll();
    }

    /**
     * Test admin can create both UG and PG sessions with the same academic year name (e.g. 2025/2026).
     */
    public function test_can_create_ug_and_pg_sessions_with_same_name()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        // 1. Create UG session named "2025/2026"
        $response1 = $this->post(route('admin.sessions.store'), [
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'type' => 'regular',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
        ]);
        $response1->assertSessionHasNoErrors();

        // 2. Create PG session ALSO named "2025/2026"
        $response2 = $this->post(route('admin.sessions.store'), [
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
        ]);
        $response2->assertSessionHasNoErrors();

        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2025/2026',
            'academic_stream' => 'UG',
        ]);
        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2025/2026',
            'academic_stream' => 'PG',
        ]);
    }

    /**
     * Test multi-calendar sessions can be created with their respective semesters/trimesters
     * and UG and PG sessions can be concurrently active without deactivating each other.
     */
    public function test_multi_calendar_concurrent_session_activation()
    {
        // 1. Create UG Semester Session
        $ugSession = Session::create([
            'name' => '2025/2026 UG Regular',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
        ]);
        $ugSem1 = Semester::create(['session_id' => $ugSession->id, 'name' => 'First Semester', 'is_current' => false]);
        $ugSem2 = Semester::create(['session_id' => $ugSession->id, 'name' => 'Second Semester', 'is_current' => false]);

        // 2. Create PG Trimester Session
        $pgSession = Session::create([
            'name' => '2025/2026 PG Executive',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
        ]);
        $pgTri1 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 1', 'is_current' => false]);
        $pgTri2 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 2', 'is_current' => false]);
        $pgTri3 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 3', 'is_current' => false]);

        // Act: Activate UG session
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $response1 = $this->post(route('admin.sessions.activate', $ugSession->id));
        $response1->assertRedirect();
        $this->assertTrue($ugSession->fresh()->is_current);

        // Act: Activate PG session
        $response2 = $this->post(route('admin.sessions.activate', $pgSession->id));
        $response2->assertRedirect();
        $this->assertTrue($pgSession->fresh()->is_current);

        // Assert: Both sessions are concurrently current!
        $this->assertTrue($ugSession->fresh()->is_current, 'UG session should remain current when PG session is activated');
        $this->assertTrue($pgSession->fresh()->is_current, 'PG session should be current');

        // Act: Create and activate another UG session (2026/2027)
        $ugSession2 = Session::create([
            'name' => '2026/2027 UG Regular',
            'start_date' => now()->addYear()->startOfYear(),
            'end_date' => now()->addYear()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
        ]);
        Semester::create(['session_id' => $ugSession2->id, 'name' => 'First Semester', 'is_current' => false]);

        $this->post(route('admin.sessions.activate', $ugSession2->id));

        // Assert: Old UG session is deactivated, new UG session is active, PG session is untouched
        $this->assertFalse($ugSession->fresh()->is_current, 'Old UG session should be deactivated');
        $this->assertTrue($ugSession2->fresh()->is_current, 'New UG session should be active');
        $this->assertTrue($pgSession->fresh()->is_current, 'PG session must still remain active');
    }

    /**
     * Test dynamic session resolution by program stream and calendar type.
     */
    public function test_dynamic_session_resolution_by_programme()
    {
        $faculty = Faculty::create(['name' => 'Management Sciences', 'code' => 'FMS']);
        $dept = Department::create(['name' => 'Business Administration', 'code' => 'BUS', 'faculty_id' => $faculty->id]);

        $ugProgram = Programme::create([
            'name' => 'B.Sc. Business Administration',
            'department_id' => $dept->id,
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'duration' => 4,
        ]);

        $pgProgram = Programme::create([
            'name' => 'Master of Business Administration',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'duration' => 2,
        ]);

        // Create and set active sessions
        $ugSession = Session::create([
            'name' => '2025/2026 UG Regular',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'is_current' => true,
        ]);

        $pgSession = Session::create([
            'name' => '2025/2026 PG Trimester',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'is_current' => true,
        ]);

        AcademicCacheService::clearAll();

        // Check model method resolution
        $resolvedUgSession = Session::currentForProgram($ugProgram);
        $resolvedPgSession = Session::currentForProgram($pgProgram);

        $this->assertNotNull($resolvedUgSession);
        $this->assertEquals($ugSession->id, $resolvedUgSession->id);

        $this->assertNotNull($resolvedPgSession);
        $this->assertEquals($pgSession->id, $resolvedPgSession->id);

        // Check stream-based resolution
        $resolvedStreamUg = Session::currentForStream('UG', 'semester');
        $resolvedStreamPg = Session::currentForStream('PG', 'trimester');

        $this->assertEquals($ugSession->id, $resolvedStreamUg->id);
        $this->assertEquals($pgSession->id, $resolvedStreamPg->id);
    }

    /**
     * Test tiered matriculation number generation for various PG and UG formats.
     */
    public function test_tiered_matriculation_number_generation_for_programmes()
    {
        $faculty = Faculty::create(['name' => 'Social Sciences', 'code' => 'SOC']);
        $dept = Department::create(['name' => 'Economics', 'code' => 'ECO', 'faculty_id' => $faculty->id]);

        $year = date('Y');

        // Case 1: Programme with dedicated custom format
        $mbaProgram = Programme::create([
            'name' => 'Executive MBA',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'matric_format' => 'MBA/{YEAR}/{SEQUENCE}',
            'duration' => 2,
        ]);

        $matric1 = MatriculationNumberHelper::generateForProgram($mbaProgram);
        $this->assertEquals("MBA/{$year}/001", $matric1);

        // Register a student with this matriculation number
        $user1 = User::factory()->create();
        Student::create([
            'user_id' => $user1->id,
            'matriculation_number' => $matric1,
            'programme_id' => $mbaProgram->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => '700',
        ]);

        // Next matriculation number for MBA should increment sequence
        $matric2 = MatriculationNumberHelper::generateForProgram($mbaProgram);
        $this->assertEquals("MBA/{$year}/002", $matric2);

        // Case 2: Postgraduate Diploma with department token
        $pgdProgram = Programme::create([
            'name' => 'PGD in Economics',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'matric_format' => 'PGD/{DEPT_CODE}/{YEAR}/{SEQUENCE}',
            'duration' => 1,
        ]);

        $matricPgd = MatriculationNumberHelper::generateForProgram($pgdProgram);
        $this->assertEquals("PGD/ECO/{$year}/001", $matricPgd);

        // Case 3: Programme with no custom format falls back to system setting or default PG template
        $mscProgram = Programme::create([
            'name' => 'MSC Economics',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 2,
        ]);

        $matricMsc = MatriculationNumberHelper::generateForProgram($mscProgram);
        // Default PG template: MIU/{PROG_CODE}/{YEAR}/{SEQUENCE} -> {PROG_CODE} from MSC Economics is MSC
        $this->assertEquals("MIU/MSC/{$year}/001", $matricMsc);

        // Case 4: Undergraduate programme backwards compatibility
        $ugProgram = Programme::create([
            'name' => 'B.Sc. Economics',
            'department_id' => $dept->id,
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'duration' => 4,
        ]);

        $shortYear = date('y');
        SystemSetting::set('matric_format', 'MIU{YEAR}{DEPT}{SEQUENCE}');
        $matricUg = MatriculationNumberHelper::generateForProgram($ugProgram, ['level' => '100']);
        $this->assertEquals("MIU{$shortYear}ECO1001", $matricUg);
    }

    /**
     * Test enrollment service correctly utilizes programme-tailored matriculation generation.
     */
    public function test_enrollment_service_generates_pg_matric_format()
    {
        $faculty = Faculty::create(['name' => 'Computing', 'code' => 'FOC']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);

        $pgSession = Session::create([
            'name' => '2025/2026 PG Trimester',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'is_current' => true,
        ]);
        Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 1', 'is_current' => true]);

        $year = date('Y');
        $pgProgram = Programme::create([
            'name' => 'Master of Information Management',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'matric_format' => 'MIU/MIM/{YEAR}/{SEQUENCE}',
            'duration' => 2,
        ]);

        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe.pg@example.com',
        ]);

        $applicant = \App\Models\Applicant::create([
            'user_id' => $user->id,
            'application_number' => 'APP-2026-001',
            'program_choice_1' => $pgProgram->id,
            'admitted_programme_id' => $pgProgram->id,
            'admitted_level' => 700,
            'status' => 'admitted',
        ]);

        $enrollmentService = app(EnrollmentService::class);
        $enrollmentService->enroll($applicant, $user->id);

        $student = Student::where('user_id', $user->id)->first();
        $this->assertNotNull($student);
        $this->assertEquals("MIU/MIM/{$year}/001", $student->matriculation_number);
    }
}
