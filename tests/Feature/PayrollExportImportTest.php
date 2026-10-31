<?php

namespace Tests\Feature;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Staff;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;
use App\Imports\PayrollImport;
use Tests\TestCase;

class PayrollExportImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Staff $staff;
    protected Payroll $payroll;
    protected PayrollItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::create([
            'name' => 'Payroll Admin',
            'email' => 'payrolladmin@portal.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole('admin');

        $dept = Department::create(['name' => 'Finance', 'code' => 'FIN']);

        $staffUser = User::create([
            'name' => 'John Doe Staff',
            'email' => 'john.staff@portal.com',
            'password' => Hash::make('password'),
        ]);

        $this->staff = Staff::create([
            'user_id' => $staffUser->id,
            'staff_number' => 'STF/2026/001',
            'department_id' => $dept->id,
            'basic_salary' => 100000.00,
            'allowances' => 15000.00,
            'deductions' => 5000.00,
        ]);

        $this->payroll = Payroll::create([
            'month' => 9,
            'year' => 2026,
            'total_amount' => 110000.00,
            'status' => 'draft',
            'generated_by' => $this->admin->id,
        ]);

        $this->item = PayrollItem::create([
            'payroll_id' => $this->payroll->id,
            'staff_id' => $this->staff->id,
            'basic_salary' => 100000.00,
            'total_allowances' => 15000.00,
            'total_deductions' => 5000.00,
            'net_salary' => 110000.00,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_export_generated_payroll()
    {
        Excel::fake();

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.finance.payroll.export', $this->payroll->id));

        $response->assertStatus(200);

        Excel::assertDownloaded("Payroll_September_2026.xlsx", function (PayrollExport $export) {
            return $export->collection()->contains('id', $this->item->id);
        });
    }

    public function test_payroll_export_includes_present_and_absent_days()
    {
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-10',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-11',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-12',
            'status' => 'absent',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-13',
            'status' => 'on_leave',
        ]);

        $export = new PayrollExport($this->payroll);
        $headings = $export->headings();
        $this->assertContains('present_days', $headings);
        $this->assertContains('absent_days', $headings);
        $this->assertContains('on_leave_days', $headings);

        $collection = $export->collection();
        $mapped = $export->map($collection->first());

        // present_days is index 9, absent_days is index 10, on_leave_days is index 11
        $this->assertEquals(2, $mapped[9]);
        $this->assertEquals(1, $mapped[10]);
        $this->assertEquals(1, $mapped[11]);
    }

    public function test_admin_can_import_and_update_generated_payroll()
    {
        $this->actingAs($this->admin);

        // Create CSV content simulating updated basic salary, allowances, deductions
        $csvHeader = "payroll_item_id,staff_id,staff_name,email,department,basic_salary,allowances,deductions,net_salary,status,remarks\n";
        $csvRow = "{$this->item->id},STF/2026/001,John Doe Staff,john.staff@portal.com,Finance,120000,20000,10000,130000,pending,Bonus added\n";
        $csvContent = $csvHeader . $csvRow;

        $file = UploadedFile::fake()->createWithContent('payroll_update.csv', $csvContent);

        $response = $this->post(route('admin.finance.payroll.import', $this->payroll->id), [
            'file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->item->refresh();
        $this->assertEquals(120000.00, (float) $this->item->basic_salary);
        $this->assertEquals(20000.00, (float) $this->item->total_allowances);
        $this->assertEquals(10000.00, (float) $this->item->total_deductions);
        $this->assertEquals(130000.00, (float) $this->item->net_salary);
        $this->assertEquals('Bonus added', $this->item->remarks);

        $this->payroll->refresh();
        $this->assertEquals(130000.00, (float) $this->payroll->total_amount);
    }

    public function test_import_fails_if_payroll_is_already_paid()
    {
        $this->payroll->update(['status' => 'paid']);

        $this->actingAs($this->admin);

        $csvContent = "payroll_item_id,staff_id,basic_salary\n{$this->item->id},STF/2026/001,150000\n";
        $file = UploadedFile::fake()->createWithContent('payroll_update.csv', $csvContent);

        $response = $this->post(route('admin.finance.payroll.import', $this->payroll->id), [
            'file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'Cannot modify payroll items for a paid payroll.');
    }

    public function test_payroll_show_view_includes_leave_days_in_attendance_stats_and_summary()
    {
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-10',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-11',
            'status' => 'late',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-12',
            'status' => 'absent',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-13',
            'status' => 'on_leave',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-14',
            'status' => 'on_leave',
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.finance.payroll.show', $this->payroll->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->has('attendanceStats.' . $this->staff->id, fn ($stats) => $stats
                ->where('present_days', 1)
                ->where('late_days', 1)
                ->where('absent_days', 1)
                ->where('leave_days', 2)
            )
            ->where('summary.total_leave_days', 2)
            ->where('summary.total_absent_days', 1)
            ->where('summary.total_late_days', 1)
        );
    }

    public function test_payroll_show_can_filter_by_department()
    {
        $otherDept = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        $otherUser = User::create([
            'name' => 'Jane Engineer',
            'email' => 'jane.eng@portal.com',
            'password' => Hash::make('password'),
        ]);
        $otherStaff = Staff::create([
            'user_id' => $otherUser->id,
            'staff_number' => 'STF/2026/002',
            'department_id' => $otherDept->id,
            'basic_salary' => 150000.00,
        ]);
        $otherItem = PayrollItem::create([
            'payroll_id' => $this->payroll->id,
            'staff_id' => $otherStaff->id,
            'basic_salary' => 150000.00,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 150000.00,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin);

        // Filter by Finance department (should only return $this->item)
        $response = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'department_id' => $this->staff->department_id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->has('items.data', 1)
            ->where('items.data.0.id', $this->item->id)
        );

        // Filter by Engineering department (should only return $otherItem)
        $response2 = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'department_id' => $otherDept->id,
        ]));

        $response2->assertStatus(200);
        $response2->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->has('items.data', 1)
            ->where('items.data.0.id', $otherItem->id)
        );
    }

    public function test_payroll_show_can_filter_by_attendance_status()
    {
        $otherUser = User::create([
            'name' => 'Bob Active',
            'email' => 'bob.active@portal.com',
            'password' => Hash::make('password'),
        ]);
        $otherStaff = Staff::create([
            'user_id' => $otherUser->id,
            'staff_number' => 'STF/2026/003',
            'department_id' => $this->staff->department_id,
            'basic_salary' => 120000.00,
        ]);
        $otherItem = PayrollItem::create([
            'payroll_id' => $this->payroll->id,
            'staff_id' => $otherStaff->id,
            'basic_salary' => 120000.00,
            'total_allowances' => 0,
            'total_deductions' => 0,
            'net_salary' => 120000.00,
            'status' => 'pending',
        ]);

        // Staff 1 is on leave
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-10',
            'status' => 'on_leave',
        ]);

        // Staff 2 is present
        \App\Models\Attendance::create([
            'staff_id' => $otherStaff->id,
            'date' => '2026-09-10',
            'status' => 'present',
        ]);

        $this->actingAs($this->admin);

        // Filter by on_leave (should only return $this->item)
        $response = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'attendance_status' => 'on_leave',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->has('items.data', 1)
            ->where('items.data.0.id', $this->item->id)
        );

        // Filter by perfect attendance (should only return $otherItem)
        $response2 = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'attendance_status' => 'perfect',
        ]));

        $response2->assertStatus(200);
        $response2->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->has('items.data', 1)
            ->where('items.data.0.id', $otherItem->id)
        );
    }

    public function test_admin_can_download_payslip_pdf()
    {
        $this->actingAs($this->admin);

        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-10',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-09-11',
            'status' => 'on_leave',
        ]);

        $response = $this->get(route('admin.finance.payroll.payslip.download', [
            'payroll' => $this->payroll->id,
            'payrollItem' => $this->item->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_view_payroll_index_with_filters_sorting_and_pagination()
    {
        $this->actingAs($this->admin);

        $payroll2 = Payroll::create([
            'month' => 8,
            'year' => 2026,
            'total_amount' => 50000.00,
            'status' => 'paid',
            'generated_by' => $this->admin->id,
            'paid_at' => now(),
        ]);

        // Default index
        $response = $this->get(route('admin.finance.payroll.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Index')
            ->has('payrolls.data', 2)
            ->has('stats')
            ->where('stats.total_payrolls', 2)
            ->where('stats.paid_count', 1)
            ->where('stats.draft_count', 1)
        );

        // Filter by status = 'paid'
        $responsePaid = $this->get(route('admin.finance.payroll.index', ['status' => 'paid']));
        $responsePaid->assertStatus(200);
        $responsePaid->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Index')
            ->has('payrolls.data', 1)
            ->where('payrolls.data.0.id', $payroll2->id)
        );

        // Sort by total_amount asc
        $responseSort = $this->get(route('admin.finance.payroll.index', [
            'sort_field' => 'total_amount',
            'sort_direction' => 'asc',
        ]));
        $responseSort->assertStatus(200);
        $responseSort->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Index')
            ->where('payrolls.data.0.id', $payroll2->id)
            ->where('payrolls.data.1.id', $this->payroll->id)
        );
    }

    public function test_admin_can_view_payroll_show_with_sorting_options()
    {
        $this->actingAs($this->admin);

        // Create second staff member with different name and salary
        $secondUser = User::create([
            'name' => 'Aaron Staff',
            'email' => 'aaron.staff@test.com',
            'password' => Hash::make('password'),
        ]);
        $secondStaff = Staff::create([
            'user_id' => $secondUser->id,
            'staff_number' => 'STF/2026/010',
            'department_id' => $this->staff->department_id,
            'basic_salary' => 300000.00,
        ]);
        $secondItem = PayrollItem::create([
            'payroll_id' => $this->payroll->id,
            'staff_id' => $secondStaff->id,
            'basic_salary' => 300000.00,
            'total_allowances' => 10000,
            'total_deductions' => 5000,
            'net_salary' => 305000.00,
            'status' => 'pending',
        ]);

        // Default / Explicit sort: name asc ('Aaron Staff' before 'John Doe Staff')
        $responseNameAsc = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'sort_field' => 'name',
            'sort_direction' => 'asc',
        ]));
        $responseNameAsc->assertStatus(200);
        $responseNameAsc->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->where('filters.sort_field', 'name')
            ->where('filters.sort_direction', 'asc')
            ->where('items.data.0.id', $secondItem->id)
            ->where('items.data.1.id', $this->item->id)
        );

        // Sort by name desc ('John Doe Staff' before 'Aaron Staff')
        $responseNameDesc = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'sort_field' => 'name',
            'sort_direction' => 'desc',
        ]));
        $responseNameDesc->assertStatus(200);
        $responseNameDesc->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->where('filters.sort_field', 'name')
            ->where('filters.sort_direction', 'desc')
            ->where('items.data.0.id', $this->item->id)
            ->where('items.data.1.id', $secondItem->id)
        );

        // Sort by net_salary desc (secondItem with 305000 should be first)
        $responseNetDesc = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'sort_field' => 'net_salary',
            'sort_direction' => 'desc',
        ]));
        $responseNetDesc->assertStatus(200);
        $responseNetDesc->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->where('filters.sort_field', 'net_salary')
            ->where('filters.sort_direction', 'desc')
            ->where('items.data.0.id', $secondItem->id)
            ->where('items.data.1.id', $this->item->id)
        );

        // Sort by net_salary asc (this->item with 110000 should be first)
        $responseNetAsc = $this->get(route('admin.finance.payroll.show', [
            'payroll' => $this->payroll->id,
            'sort_field' => 'net_salary',
            'sort_direction' => 'asc',
        ]));
        $responseNetAsc->assertStatus(200);
        $responseNetAsc->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Payroll/Show')
            ->where('filters.sort_field', 'net_salary')
            ->where('filters.sort_direction', 'asc')
            ->where('items.data.0.id', $this->item->id)
            ->where('items.data.1.id', $secondItem->id)
        );
    }
}
