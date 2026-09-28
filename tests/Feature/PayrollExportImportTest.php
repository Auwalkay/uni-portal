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
}
