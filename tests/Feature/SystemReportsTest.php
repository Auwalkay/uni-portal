<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_unauthorized_users_cannot_access_system_reports()
    {
        // 1. Guest user
        $response = $this->get(route('admin.reports.index'));
        $response->assertRedirect(route('login'));

        // 2. Student user (unauthorized)
        $studentUser = User::factory()->create();
        $studentUser->assignRole('student');

        $response = $this->actingAs($studentUser)->get(route('admin.reports.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_admin_can_access_system_reports_page()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_reports@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // Create some basic setup data to avoid empty relation errors if any
        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'sci']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'csc', 'faculty_id' => $faculty->id]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Reports/Index')
                ->has('academicStats')
                ->has('financeStats')
                ->has('attendanceStats')
                ->has('hostelStats')
                ->has('libraryStats')
                ->has('sickbayStats')
                ->has('inventoryStats')
        );
    }

    public function test_authorized_admin_can_export_master_excel_report()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_reports_export@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));
        
        // Assert it is an Excel download response
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=system_master_report_' . now()->format('Y_m_d_His') . '.xlsx');
    }

    public function test_authorized_admin_can_filter_reports_by_faculty_department_programme_level_gender_mode()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_reports_filter@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'sci']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'csc', 'faculty_id' => $faculty->id]);
        $programme = Programme::create([
            'name' => 'Computer Science',
            'department_id' => $dept->id,
            'type' => 'UG',
            'duration' => 4,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'faculty_id' => $faculty->id,
            'department_id' => $dept->id,
            'program_id' => $programme->id,
            'level' => '100',
            'gender' => 'male',
            'entry_mode' => 'UTME',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Reports/Index')
                ->where('filters.faculty_id', (string) $faculty->id)
                ->where('filters.department_id', (string) $dept->id)
                ->where('filters.program_id', (string) $programme->id)
                ->where('filters.level', '100')
                ->where('filters.gender', 'male')
                ->where('filters.entry_mode', 'UTME')
        );
    }

    public function test_authorized_admin_can_filter_reports_by_date_range()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_reports_date@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.index', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Reports/Index')
                ->where('filters.start_date', '2026-06-01')
                ->where('filters.end_date', '2026-06-30')
        );
    }
}
