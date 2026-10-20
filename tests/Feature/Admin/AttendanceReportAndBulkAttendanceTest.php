<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceReportAndBulkAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Faculty $faculty;
    protected Department $department;
    protected Staff $activeStaff1;
    protected Staff $activeStaff2;
    protected Staff $inactiveStaff;
    protected Staff $softDeletedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup permissions and admin user
        Permission::findOrCreate('access_admin_dashboard');
        Permission::findOrCreate('view_staff');
        Permission::findOrCreate('view_attendance');
        Permission::findOrCreate('manage_attendance');

        $role = Role::findOrCreate('Super Admin');
        $role->givePermissionTo([
            'access_admin_dashboard',
            'view_staff',
            'view_attendance',
            'manage_attendance',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin HR',
            'email' => 'admin.hr@portal.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        $this->faculty = Faculty::create([
            'name' => 'Faculty of Science',
            'code' => 'SCI',
        ]);

        $this->department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CS',
            'faculty_id' => $this->faculty->id,
        ]);

        // Active Staff 1
        $user1 = User::create([
            'name' => 'Active Staff One',
            'email' => 'staff1@portal.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->activeStaff1 = Staff::create([
            'user_id' => $user1->id,
            'department_id' => $this->department->id,
            'staff_number' => 'STF-001',
        ]);

        // Active Staff 2
        $user2 = User::create([
            'name' => 'Active Staff Two',
            'email' => 'staff2@portal.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->activeStaff2 = Staff::create([
            'user_id' => $user2->id,
            'department_id' => $this->department->id,
            'staff_number' => 'STF-002',
        ]);

        // Inactive Staff (is_active = false)
        $user3 = User::create([
            'name' => 'Inactive Staff Member',
            'email' => 'inactive@portal.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);
        $this->inactiveStaff = Staff::create([
            'user_id' => $user3->id,
            'department_id' => $this->department->id,
            'staff_number' => 'STF-INACTIVE',
        ]);

        // Soft-deleted user Staff
        $user4 = User::create([
            'name' => 'Soft Deleted Staff Member',
            'email' => 'deleted@portal.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user4->delete(); // Soft delete
        $this->softDeletedStaff = Staff::create([
            'user_id' => $user4->id,
            'department_id' => $this->department->id,
            'staff_number' => 'STF-DELETED',
        ]);
    }

    public function test_attendance_index_only_pulls_staff_with_active_profile(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.index'));

        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $page->component('Admin/HR/Attendance/Index');
            $allStaff = collect($page->toArray()['props']['allStaff']);

            // Must include active staff
            $this->assertTrue($allStaff->contains('id', $this->activeStaff1->id));
            $this->assertTrue($allStaff->contains('id', $this->activeStaff2->id));

            // Must exclude inactive staff and soft-deleted user staff
            $this->assertFalse($allStaff->contains('id', $this->inactiveStaff->id));
            $this->assertFalse($allStaff->contains('id', $this->softDeletedStaff->id));
        });
    }

    public function test_bulk_attendance_stores_only_submitted_staff(): void
    {
        $date = now()->toDateString();

        // Submit attendance for only 1 of the 2 active staff
        $payload = [
            'date' => $date,
            'attendances' => [
                [
                    'staff_id' => $this->activeStaff1->id,
                    'status' => 'present',
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                    'notes' => 'On time',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.bulk-store'), $payload);

        $response->assertSessionHas('success');

        // Verify activeStaff1 has a record
        $this->assertDatabaseHas('attendances', [
            'staff_id' => $this->activeStaff1->id,
            'date' => $date,
            'status' => 'present',
        ]);

        // Verify activeStaff2 was NOT marked as present by default
        $this->assertDatabaseMissing('attendances', [
            'staff_id' => $this->activeStaff2->id,
            'date' => $date,
        ]);
    }

    public function test_bulk_attendance_rejects_inactive_staff_member(): void
    {
        $date = now()->toDateString();

        $payload = [
            'date' => $date,
            'attendances' => [
                [
                    'staff_id' => $this->inactiveStaff->id,
                    'status' => 'present',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.attendance.bulk-store'), $payload);

        $response->assertSessionHasErrors(['attendances.0.staff_id']);
    }

    public function test_attendance_report_accurately_counts_leave_and_excludes_inactive_staff(): void
    {
        $date = now()->format('Y-m-d');

        // Create attendance records
        // Active staff 1: 1 present, 1 on_leave
        Attendance::create([
            'staff_id' => $this->activeStaff1->id,
            'date' => now()->startOfMonth()->toDateString(),
            'status' => 'present',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'source' => 'manual',
        ]);
        Attendance::create([
            'staff_id' => $this->activeStaff1->id,
            'date' => now()->startOfMonth()->addDay()->toDateString(),
            'status' => 'on_leave',
            'source' => 'manual',
            'notes' => 'Annual leave',
        ]);

        // Inactive staff has an old attendance record that should be ignored
        Attendance::create([
            'staff_id' => $this->inactiveStaff->id,
            'date' => now()->startOfMonth()->toDateString(),
            'status' => 'on_leave',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.reports', [
                'type' => 'monthly',
                'date' => $date,
            ]));

        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $page->component('Admin/HR/Attendance/Reports');
            $props = $page->toArray()['props'];

            $overallStats = $props['overallStats'];
            // Total leave should be 1 (only activeStaff1, not inactiveStaff)
            $this->assertEquals(1, $overallStats['total_leave']);

            $stats = collect($props['stats']);
            $staff1Stat = $stats->firstWhere('staff_id', $this->activeStaff1->id);
            $this->assertNotNull($staff1Stat);
            $this->assertEquals(1, $staff1Stat['leave_count']);
            $this->assertEquals(1, $staff1Stat['present_count']);

            // Inactive staff must NOT be in stats
            $inactiveStat = $stats->firstWhere('staff_id', $this->inactiveStaff->id);
            $this->assertNull($inactiveStat);

            // Department summary
            $deptSummary = collect($props['departmentSummary']);
            $deptStat = $deptSummary->firstWhere('department_name', 'Computer Science');
            $this->assertNotNull($deptStat);
            $this->assertEquals(1, $deptStat['leave_count']);
        });
    }

    public function test_attendance_report_export_pdf_and_excel(): void
    {
        Attendance::create([
            'staff_id' => $this->activeStaff1->id,
            'date' => now()->startOfMonth()->toDateString(),
            'status' => 'on_leave',
            'source' => 'manual',
        ]);

        $pdfResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.export', [
                'format' => 'pdf',
                'type' => 'monthly',
                'date' => now()->toDateString(),
            ]));

        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));

        $excelResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.export', [
                'format' => 'excel',
                'type' => 'monthly',
                'date' => now()->toDateString(),
            ]));

        $excelResponse->assertStatus(200);
    }

    public function test_attendance_report_supports_custom_date_range(): void
    {
        // Record inside custom range
        Attendance::create([
            'staff_id' => $this->activeStaff1->id,
            'date' => '2026-03-10',
            'status' => 'present',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'source' => 'manual',
        ]);
        Attendance::create([
            'staff_id' => $this->activeStaff1->id,
            'date' => '2026-03-15',
            'status' => 'on_leave',
            'source' => 'manual',
        ]);

        // Record outside custom range
        Attendance::create([
            'staff_id' => $this->activeStaff1->id,
            'date' => '2026-04-05',
            'status' => 'present',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.reports', [
                'type' => 'custom',
                'from_date' => '2026-03-01',
                'to_date' => '2026-03-31',
            ]));

        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $page->component('Admin/HR/Attendance/Reports');
            $props = $page->toArray()['props'];

            $this->assertEquals('Mar 01, 2026 - Mar 31, 2026', $props['reportTitle']);
            $this->assertEquals('2026-03-01', $props['dateRange']['start']);
            $this->assertEquals('2026-03-31', $props['dateRange']['end']);

            $overallStats = $props['overallStats'];
            $this->assertEquals(1, $overallStats['total_present']);
            $this->assertEquals(1, $overallStats['total_leave']);

            $stats = collect($props['stats']);
            $staff1Stat = $stats->firstWhere('staff_id', $this->activeStaff1->id);
            $this->assertNotNull($staff1Stat);
            $this->assertEquals(2, $staff1Stat['total_days']);
        });

        // Test export with custom date range
        $pdfResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.export', [
                'format' => 'pdf',
                'type' => 'custom',
                'from_date' => '2026-03-01',
                'to_date' => '2026-03-31',
            ]));

        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_attendance_report_supports_pagination_and_per_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.reports', ['per_page' => 25]));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Admin/HR/Attendance/Reports');
            $props = $page->toArray()['props'];
            $this->assertEquals(25, $props['filters']['per_page']);
        });

        // Test clamping: per_page < 5 clamped to 5, > 100 clamped to 100
        $responseClampedLow = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.reports', ['per_page' => 2]));
        $responseClampedLow->assertInertia(fn ($page) => $page->where('filters.per_page', 5));

        $responseClampedHigh = $this->actingAs($this->adminUser)
            ->get(route('admin.attendance.reports', ['per_page' => 500]));
        $responseClampedHigh->assertInertia(fn ($page) => $page->where('filters.per_page', 100));
    }
}
