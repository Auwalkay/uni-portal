<?php

namespace Tests\Feature\Admin;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PostgraduateRolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentStreamPermissionAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;
    private Department $dept;
    private Programme $ugProgramme;
    private Programme $pgProgramme;
    private Session $session;
    private Student $ugStudent;
    private Student $pgStudent;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_ug_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_pg_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_global_analytics', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_admission_stats', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'applicant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->session = Session::create([
            'name' => '2026/2027',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(10),
            'is_current' => true,
        ]);

        $this->faculty = Faculty::create(['name' => 'Management Sciences', 'code' => 'MGT']);
        $this->dept = Department::create(['faculty_id' => $this->faculty->id, 'name' => 'Accounting', 'code' => 'ACC']);

        $this->ugProgramme = Programme::create([
            'department_id' => $this->dept->id,
            'name' => 'B.Sc. Accounting',
            'type' => 'BSC',
            'academic_stream' => 'UG',
            'duration' => 4.0,
            'is_active' => true,
        ]);

        $this->pgProgramme = Programme::create([
            'department_id' => $this->dept->id,
            'name' => 'M.Sc. Accounting',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'duration' => 1.5,
            'is_active' => true,
        ]);

        $ugUser = User::factory()->create(['name' => 'Umar Undergraduate']);
        $this->ugStudent = Student::create([
            'user_id' => $ugUser->id,
            'matriculation_number' => 'MIU/UG/ACC/001',
            'department_id' => $this->dept->id,
            'program_id' => $this->ugProgramme->id,
            'academic_stream' => 'UG',
            'current_level' => '100',
            'admitted_session_id' => $this->session->id,
        ]);

        $pgUser = User::factory()->create(['name' => 'Patience Postgraduate']);
        $this->pgStudent = Student::create([
            'user_id' => $pgUser->id,
            'matriculation_number' => 'MIU/PG/ACC/001',
            'department_id' => $this->dept->id,
            'program_id' => $this->pgProgramme->id,
            'academic_stream' => 'PG',
            'current_level' => '700',
            'admitted_session_id' => $this->session->id,
        ]);
    }

    public function test_view_pg_students_permission_scopes_list_and_detail_to_pg_only(): void
    {
        $pgViewer = User::factory()->create();
        $pgViewer->givePermissionTo(['access_admin_dashboard', 'view_pg_students']);

        $response = $this->actingAs($pgViewer)->get(route('admin.students.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Students/Index')
            ->has('students.data', 1)
            ->where('students.data.0.id', $this->pgStudent->id)
            ->where('stats.total', 1)
            ->where('stats.pg', 1)
            ->where('permissions.can_view_pg', true)
            ->where('permissions.can_view_ug', false)
        );

        $this->actingAs($pgViewer)->get(route('admin.students.show', $this->pgStudent))->assertStatus(200);
        $this->actingAs($pgViewer)->get(route('admin.students.show', $this->ugStudent))->assertStatus(403);
    }

    public function test_view_ug_students_permission_scopes_list_and_detail_to_ug_only(): void
    {
        $ugViewer = User::factory()->create();
        $ugViewer->givePermissionTo(['access_admin_dashboard', 'view_ug_students']);

        $response = $this->actingAs($ugViewer)->get(route('admin.students.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Students/Index')
            ->has('students.data', 1)
            ->where('students.data.0.id', $this->ugStudent->id)
            ->where('stats.total', 1)
            ->where('stats.ug', 1)
            ->where('permissions.can_view_ug', true)
            ->where('permissions.can_view_pg', false)
        );

        $this->actingAs($ugViewer)->get(route('admin.students.show', $this->ugStudent))->assertStatus(200);
        $this->actingAs($ugViewer)->get(route('admin.students.show', $this->pgStudent))->assertStatus(403);
    }

    public function test_view_students_permission_sees_all_and_filters_by_stream(): void
    {
        $allViewer = User::factory()->create();
        $allViewer->givePermissionTo(['access_admin_dashboard', 'view_students']);

        $response = $this->actingAs($allViewer)->get(route('admin.students.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Students/Index')
            ->has('students.data', 2)
            ->where('stats.total', 2)
            ->where('stats.ug', 1)
            ->where('stats.pg', 1)
            ->where('permissions.can_view_ug', true)
            ->where('permissions.can_view_pg', true)
        );

        $pgFiltered = $this->actingAs($allViewer)->get(route('admin.students.index', ['academic_stream' => 'PG']));
        $pgFiltered->assertInertia(fn ($page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $this->pgStudent->id)
        );
    }

    public function test_dean_spgs_seeder_grants_admin_dashboard_and_pg_student_access(): void
    {
        $this->seed(PostgraduateRolesAndPermissionsSeeder::class);

        $deanSpgs = User::factory()->create();
        $deanSpgs->assignRole('dean_spgs');

        $this->actingAs($deanSpgs)->get(route('admin.admissions.pg.index'))->assertStatus(200);
        $this->actingAs($deanSpgs)->get(route('admin.students.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.id', $this->pgStudent->id)
            );
    }

    public function test_admin_dashboard_includes_ug_and_pg_student_and_applicant_counts(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo([
            'access_admin_dashboard',
            'view_global_analytics',
            'view_admission_stats',
        ]);

        $ugAppUser = User::factory()->create();
        $ugAppUser->assignRole('applicant');
        Applicant::create([
            'user_id' => $ugAppUser->id,
            'application_number' => 'UG-2026-001',
            'application_mode' => 'UTME',
            'program_choice_1' => $this->ugProgramme->id,
            'status' => 'pending',
        ]);

        $pgAppUser = User::factory()->create();
        $pgAppUser->assignRole('applicant');
        Applicant::create([
            'user_id' => $pgAppUser->id,
            'application_number' => 'PG-2026-001',
            'application_mode' => 'PG',
            'program_choice_1' => $this->pgProgramme->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard', ['refresh' => 'true']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('stats.total_students', 2)
            ->where('stats.ug_students', 1)
            ->where('stats.pg_students', 1)
            ->where('stats.ug_applicants', 1)
            ->where('stats.pg_applicants', 1)
            ->where('stats.admissions_funnel.ug_applicants', 1)
            ->where('stats.admissions_funnel.pg_applicants', 1)
        );
    }
}
