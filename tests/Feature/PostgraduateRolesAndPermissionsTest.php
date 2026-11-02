<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\PostgraduateResearchLog;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Models\PostgraduateStaffAccreditation;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Postgraduate\PostgraduateResearchService;
use App\Services\Postgraduate\PostgraduateStaffGovernanceService;
use Database\Seeders\PostgraduateRolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgraduateRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureStudentProfileCompleted::class);
        Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $this->seed(PostgraduateRolesAndPermissionsSeeder::class);
    }

    protected function createAcademicEnvironment(): array
    {
        $faculty = Faculty::create(['name' => 'Faculty of Science', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $phdProg = Programme::create([
            'name' => 'Ph.D Computer Science',
            'department_id' => $dept->id,
            'type' => 'PHD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 3,
        ]);

        $mscProg = Programme::create([
            'name' => 'M.Sc Computer Science',
            'department_id' => $dept->id,
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 2,
        ]);

        return compact('faculty', 'dept', 'session', 'phdProg', 'mscProg');
    }

    protected function createStaffMember(string $name, string $email, string $staffNum, string $departmentId, array $permissions = []): Staff
    {
        $user = User::factory()->create(['name' => $name, 'email' => $email]);
        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        return Staff::create([
            'user_id' => $user->id,
            'staff_number' => $staffNum,
            'designation' => 'Senior Lecturer',
            'department_id' => $departmentId,
            'is_academic' => true,
        ]);
    }

    public function test_postgraduate_permissions_are_seeded_correctly(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'manage_postgraduate_school']);
        $this->assertDatabaseHas('permissions', ['name' => 'manage_pg_supervisors']);
        $this->assertDatabaseHas('permissions', ['name' => 'supervise_pg_students']);
        $this->assertDatabaseHas('permissions', ['name' => 'score_viva_defense']);

        $deanSpgsRole = Role::findByName('dean_spgs');
        $this->assertTrue($deanSpgsRole->hasPermissionTo('manage_postgraduate_school'));
        $this->assertTrue($deanSpgsRole->hasPermissionTo('manage_pg_supervisors'));

        $dpgcChairRole = Role::findByName('dpgc_chair');
        $this->assertTrue($dpgcChairRole->hasPermissionTo('view_pg_dashboard'));
        $this->assertTrue($dpgcChairRole->hasPermissionTo('manage_pg_supervisors'));
    }

    public function test_supervisor_quota_enforcement_prevents_exceeding_phd_limit(): void
    {
        $env = $this->createAcademicEnvironment();
        $supervisor = $this->createStaffMember('Prof. Abubakar', 'abubakar@pg.edu', 'STF/001', $env['dept']->id, ['supervise_pg_students']);

        PostgraduateStaffAccreditation::create([
            'staff_id' => $supervisor->id,
            'is_phd_accredited' => true,
            'is_masters_accredited' => true,
            'max_phd_quota' => 2, // capped at 2 for this test
            'max_masters_quota' => 5,
        ]);

        $governanceService = app(PostgraduateStaffGovernanceService::class);
        $researchService = app(PostgraduateResearchService::class);

        // Create 2 Ph.D candidate projects for this supervisor
        for ($i = 1; $i <= 2; $i++) {
            $studentUser = User::factory()->create();
            $student = Student::create([
                'user_id' => $studentUser->id,
                'matriculation_number' => "PG/PHD/00{$i}",
                'spgs_file_number' => "SPGS/00{$i}",
                'program_id' => $env['phdProg']->id,
                'current_level' => 900,
                'status' => 'active',
            ]);

            PostgraduateResearchProject::create([
                'student_id' => $student->id,
                'major_supervisor_id' => $supervisor->id,
                'title' => "Ph.D Thesis {$i}",
                'status' => 'in_progress',
            ]);
        }

        // Supervisor now has 2/2 Ph.D candidates
        $this->assertFalse($governanceService->canSuperviseMore($supervisor, 'PHD'));

        // Attempting to allocate 3rd Ph.D candidate should throw ValidationException
        $thirdUser = User::factory()->create();
        $thirdStudent = Student::create([
            'user_id' => $thirdUser->id,
            'matriculation_number' => 'PG/PHD/003',
            'spgs_file_number' => 'SPGS/003',
            'program_id' => $env['phdProg']->id,
            'current_level' => 900,
            'status' => 'active',
        ]);

        $thirdProject = PostgraduateResearchProject::create([
            'student_id' => $thirdStudent->id,
            'title' => 'Ph.D Thesis 3',
            'status' => 'submitted',
        ]);

        $this->expectException(ValidationException::class);
        $researchService->allocateSupervisors($thirdProject, [
            'major_supervisor_id' => $supervisor->id,
        ]);
    }

    public function test_supervisor_workbench_view_is_accessible_to_authorized_lecturers(): void
    {
        $env = $this->createAcademicEnvironment();
        $supervisor = $this->createStaffMember('Dr. Fatima Bello', 'fatima@pg.edu', 'STF/002', $env['dept']->id, ['supervise_pg_students']);

        // Student allocated to Dr. Fatima
        $studentUser = User::factory()->create(['name' => 'Aliyu Hassan']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'matriculation_number' => 'PG/MSC/001',
            'spgs_file_number' => 'SPGS/MSC/001',
            'program_id' => $env['mscProg']->id,
            'current_level' => 800,
            'status' => 'active',
        ]);

        $project = PostgraduateResearchProject::create([
            'student_id' => $student->id,
            'major_supervisor_id' => $supervisor->id,
            'title' => 'Distributed Ledger Performance in Cloud Architectures',
            'status' => 'in_progress',
        ]);

        // Submit a pending log entry
        $log = PostgraduateResearchLog::create([
            'project_id' => $project->id,
            'student_id' => $student->id,
            'entry_date' => now()->toDateString(),
            'hours_spent' => 6,
            'work_summary' => 'Benchmarking Raft consensus latency across 10 geo-distributed nodes.',
            'supervisor_status' => 'pending',
        ]);

        $response = $this->actingAs($supervisor->user)->get(route('staff.postgraduate.supervision.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Staff/Postgraduate/Supervision')
            ->has('projects.data', 1)
            ->has('pendingLogs', 1)
            ->where('stats.total_candidates', 1)
        );
    }

    public function test_supervisor_can_review_logbook_entry(): void
    {
        $env = $this->createAcademicEnvironment();
        $supervisor = $this->createStaffMember('Dr. Fatima Bello', 'fatima@pg.edu', 'STF/002', $env['dept']->id, ['supervise_pg_students', 'review_pg_logbooks']);

        $studentUser = User::factory()->create(['name' => 'Aliyu Hassan']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'matriculation_number' => 'PG/MSC/001',
            'spgs_file_number' => 'SPGS/MSC/001',
            'program_id' => $env['mscProg']->id,
            'current_level' => 800,
            'status' => 'active',
        ]);

        $project = PostgraduateResearchProject::create([
            'student_id' => $student->id,
            'major_supervisor_id' => $supervisor->id,
            'title' => 'Distributed Ledger Performance in Cloud Architectures',
            'status' => 'in_progress',
        ]);

        $log = PostgraduateResearchLog::create([
            'project_id' => $project->id,
            'student_id' => $student->id,
            'entry_date' => now()->toDateString(),
            'hours_spent' => 6,
            'work_summary' => 'Benchmarking Raft consensus latency across 10 geo-distributed nodes.',
            'supervisor_status' => 'pending',
        ]);

        $response = $this->actingAs($supervisor->user)->post(route('staff.postgraduate.supervision.logs.review', $log->id), [
            'supervisor_status' => 'reviewed',
            'supervisor_feedback' => 'Comprehensive benchmarks. Verify packet loss under high latency next.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('postgraduate_research_logs', [
            'id' => $log->id,
            'supervisor_status' => 'reviewed',
            'supervisor_feedback' => 'Comprehensive benchmarks. Verify packet loss under high latency next.',
        ]);
    }

    public function test_admin_pg_staff_directory_requires_permission(): void
    {
        $unauthorizedUser = User::factory()->create();
        $response = $this->actingAs($unauthorizedUser)->get(route('admin.postgraduate.staff.index'));
        $response->assertStatus(403);

        $adminUser = User::factory()->create();
        $adminUser->givePermissionTo('access_admin_dashboard');
        $adminUser->givePermissionTo('manage_postgraduate_school');

        $response = $this->actingAs($adminUser)->get(route('admin.postgraduate.staff.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Postgraduate/Staff/Index')
            ->has('staffList.data')
            ->has('metrics')
        );
    }
}
