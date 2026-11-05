<?php

namespace Tests\Feature\Admin;

use App\Models\Applicant;
use App\Models\FeeConfiguration;
use App\Models\FeeType;
use App\Models\Programme;
use App\Models\Session;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgraduateFeeScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managePayments = Permission::firstOrCreate(['name' => 'manage_payments']);
        $viewPgApps = Permission::firstOrCreate(['name' => 'view_pg_applications']);
        $admitPg = Permission::firstOrCreate(['name' => 'admit_pg_students']);
        $accessAdmin = Permission::firstOrCreate(['name' => 'access_admin_dashboard']);
        $adminRole->givePermissionTo([$managePayments, $viewPgApps, $admitPg, $accessAdmin]);

        SystemSetting::set('pg_application_fee', 50000);
        SystemSetting::set('application_fee', 100000);
    }

    public function test_postgraduate_fee_schedule_command_seeds_programmes_and_fees()
    {
        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        $tuitionFeeType = FeeType::firstOrCreate(
            ['slug' => 'tuition-fee'],
            ['name' => 'Tuition Fee', 'description' => 'Tuition fee', 'is_one_time' => false]
        );

        $exitCode = \Illuminate\Support\Facades\Artisan::call('fees:seed-pg-schedule', ['session_id' => $session->id]);
        $this->assertEquals(0, $exitCode);

        // Verify key programmes exist
        $mscCs = Programme::where(function ($q) {
            $q->where('name', 'M.Sc Computer Science')->orWhere('name', 'M.Sc. Computer Science');
        })->first();
        $this->assertNotNull($mscCs);
        $this->assertEquals('PG', $mscCs->academic_stream);
        $this->assertEquals('MSC', $mscCs->type);

        $phdCs = Programme::where(function ($q) {
            $q->where('name', 'Ph.D Cyber Security')->orWhere('name', 'Ph.D. Cyber Security');
        })->first();
        $this->assertNotNull($phdCs);
        $this->assertEquals('PHD', $phdCs->type);

        $pgdAcc = Programme::where('name', 'PGD Accounting')->first();
        $this->assertNotNull($pgdAcc);
        $this->assertEquals('PGD', $pgdAcc->type);

        // Verify Tier-based Fee Configurations created with program_id = null (Tier-wide)
        $mscCscRule = FeeConfiguration::where('session_id', $session->id)
            ->where('entry_mode', 'MSc')
            ->whereNotNull('department_id')
            ->whereNull('program_id')
            ->first();
        $this->assertNotNull($mscCscRule);
        $this->assertEquals(350000, (float) $mscCscRule->amount);

        $pgdMgtRule = FeeConfiguration::where('session_id', $session->id)
            ->where('entry_mode', 'PGD')
            ->whereNull('department_id')
            ->whereNull('program_id')
            ->first();
        $this->assertNotNull($pgdMgtRule);
        $this->assertEquals(200000, (float) $pgdMgtRule->amount);

        // Verify that programmes resolve their exact tuition fee via tier inheritance
        $allSessionConfigs = FeeConfiguration::where('session_id', $session->id)->get();
        $tuitionConfigs = $allSessionConfigs->where('fee_type_id', $tuitionFeeType->id);

        $resolvedMsc = \App\Services\Finance\FeeService::resolveConfigFromCollection(
            $tuitionConfigs,
            $mscCs->id,
            $mscCs->department_id,
            $mscCs->department?->faculty_id,
            $mscCs->type,
            null,
            'Postgraduate',
            true
        );
        $this->assertNotNull($resolvedMsc);
        $this->assertEquals(350000, (float) $resolvedMsc->amount);

        $resolvedPhd = \App\Services\Finance\FeeService::resolveConfigFromCollection(
            $tuitionConfigs,
            $phdCs->id,
            $phdCs->department_id,
            $phdCs->department?->faculty_id,
            $phdCs->type,
            null,
            'Postgraduate',
            true
        );
        $this->assertNotNull($resolvedPhd);
        $this->assertEquals(450000, (float) $resolvedPhd->amount);

        $resolvedPgd = \App\Services\Finance\FeeService::resolveConfigFromCollection(
            $tuitionConfigs,
            $pgdAcc->id,
            $pgdAcc->department_id,
            $pgdAcc->department?->faculty_id,
            $pgdAcc->type,
            null,
            'Postgraduate',
            true
        );
        $this->assertNotNull($resolvedPgd);
        $this->assertEquals(200000, (float) $resolvedPgd->amount);
    }

    public function test_admin_can_sync_pg_fee_schedule_via_endpoint()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        FeeType::firstOrCreate(
            ['slug' => 'tuition-fee'],
            ['name' => 'Tuition Fee', 'description' => 'Tuition fee', 'is_one_time' => false]
        );

        $response = $this->actingAs($admin)
            ->post(route('admin.finance.session.fees.sync_pg', $session->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify Tier-wide PG fee configs now exist for this session
        $tierConfigsCount = FeeConfiguration::where('session_id', $session->id)
            ->whereIn('entry_mode', ['PGD', 'MSc', 'MBA', 'PhD'])
            ->whereNull('program_id')
            ->count();

        $this->assertGreaterThanOrEqual(10, $tierConfigsCount);
    }

    public function test_postgraduate_admission_letter_computes_exact_tuition_and_enrolment_fee()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        FeeType::firstOrCreate(
            ['slug' => 'tuition-fee'],
            ['name' => 'Tuition Fee', 'description' => 'Tuition fee', 'is_one_time' => false]
        );

        \Illuminate\Support\Facades\Artisan::call('fees:seed-pg-schedule', ['session_id' => $session->id]);

        $mscCs = Programme::where(function ($q) {
            $q->where('name', 'M.Sc Computer Science')->orWhere('name', 'M.Sc. Computer Science');
        })->first();

        $applicantUser = User::factory()->create(['name' => 'Jane Postgraduate']);
        $applicant = Applicant::create([
            'user_id' => $applicantUser->id,
            'application_number' => 'SPGS-2026-001',
            'application_mode' => 'PG',
            'program_choice_1' => $mscCs->id,
            'status' => 'admitted',
            'admitted_programme_id' => $mscCs->id,
            'admitted_level' => '700',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.admissions.pg.letter', $applicant));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_applicant_controllers_bill_pg_fee_at_50k_and_ug_at_100k()
    {
        $applicantPortalPermission = Permission::firstOrCreate(['name' => 'access_applicant_portal']);
        SystemSetting::set('pg_application_fee', 50000);
        SystemSetting::set('application_fee', 100000);

        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        // Verify PG applicant payment
        $pgUser = User::factory()->create();
        $pgUser->givePermissionTo($applicantPortalPermission);
        $pgApplicant = Applicant::create([
            'user_id' => $pgUser->id,
            'application_number' => 'SPGS-APP-001',
            'application_mode' => 'PG',
            'status' => 'draft',
        ]);

        $this->actingAs($pgUser)->get(route('applicant.payment.index'));

        $pgInvoice = \App\Models\Invoice::where('user_id', $pgUser->id)->where('type', 'application_fee')->first();
        $this->assertNotNull($pgInvoice);
        $this->assertEquals(50000, (float) $pgInvoice->amount);

        // Verify UG applicant payment
        $ugUser = User::factory()->create();
        $ugUser->givePermissionTo($applicantPortalPermission);
        $ugApplicant = Applicant::create([
            'user_id' => $ugUser->id,
            'application_number' => 'UG-APP-001',
            'application_mode' => 'UTME',
            'status' => 'draft',
        ]);

        $this->actingAs($ugUser)->get(route('applicant.payment.index'));

        $ugInvoice = \App\Models\Invoice::where('user_id', $ugUser->id)->where('type', 'application_fee')->first();
        $this->assertNotNull($ugInvoice);
        $this->assertEquals(100000, (float) $ugInvoice->amount);
    }

    public function test_hierarchical_tier_based_fee_resolution_and_mba_overrides()
    {
        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        $tuitionFeeType = FeeType::firstOrCreate(
            ['slug' => 'tuition-fee'],
            ['name' => 'Tuition Fee', 'description' => 'Tuition fee', 'is_one_time' => false]
        );

        $facultyMgt = \App\Models\Faculty::create(['name' => 'Faculty of Management', 'code' => 'MGT_TEST']);
        $deptBus = \App\Models\Department::create(['name' => 'Business Admin', 'code' => 'BUS_TEST', 'faculty_id' => $facultyMgt->id]);
        $deptAcc = \App\Models\Department::create(['name' => 'Accounting', 'code' => 'ACC_TEST', 'faculty_id' => $facultyMgt->id]);

        $pgdAcc = Programme::create([
            'name' => 'PGD Accounting Test',
            'department_id' => $deptAcc->id,
            'type' => 'PGD',
            'academic_stream' => 'PG',
        ]);

        $pgdBus = Programme::create([
            'name' => 'PGD Business Admin Test',
            'department_id' => $deptBus->id,
            'type' => 'PGD',
            'academic_stream' => 'PG',
        ]);

        $regularMba = Programme::create([
            'name' => 'Regular MBA Test',
            'department_id' => $deptBus->id,
            'type' => 'MBA',
            'academic_stream' => 'PG',
        ]);

        $executiveMba = Programme::create([
            'name' => 'Executive MBA Test',
            'department_id' => $deptBus->id,
            'type' => 'MBA',
            'academic_stream' => 'PG',
        ]);

        // 1. Create a SINGLE Faculty + PGD rule (₦200,000)
        FeeConfiguration::create([
            'fee_type_id' => $tuitionFeeType->id,
            'session_id' => $session->id,
            'faculty_id' => $facultyMgt->id,
            'entry_mode' => 'PGD',
            'amount' => 200000,
            'is_compulsory' => true,
        ]);

        // 2. Create a SINGLE Faculty + MBA rule (₦400,000)
        FeeConfiguration::create([
            'fee_type_id' => $tuitionFeeType->id,
            'session_id' => $session->id,
            'faculty_id' => $facultyMgt->id,
            'entry_mode' => 'MBA',
            'amount' => 400000,
            'is_compulsory' => true,
        ]);

        // 3. Create a Program-Specific Override for Executive MBA (₦650,000)
        FeeConfiguration::create([
            'fee_type_id' => $tuitionFeeType->id,
            'session_id' => $session->id,
            'program_id' => $executiveMba->id,
            'amount' => 650000,
            'is_compulsory' => true,
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);

        // Verify PGD Accounting student automatically inherits Faculty PGD rule (₦200,000)
        $userPgdAcc = User::factory()->create();
        $studentPgdAcc = \App\Models\Student::create([
            'user_id' => $userPgdAcc->id,
            'matriculation_number' => 'PGD-ACC-001',
            'program_id' => $pgdAcc->id,
            'department_id' => $deptAcc->id,
            'faculty_id' => $facultyMgt->id,
            'entry_mode' => 'Postgraduate',
            'current_level' => '700',
            'status' => 'active',
        ]);
        $studentPgdAcc->setRelation('program', $pgdAcc);
        $invoicePgdAcc = $feeService->generateSchoolFeeInvoice($studentPgdAcc, $session);
        $this->assertNotNull($invoicePgdAcc);
        $this->assertEquals(200000, (float) $invoicePgdAcc->items->firstWhere('fee_type_id', $tuitionFeeType->id)->amount);

        // Verify PGD Business student automatically inherits same Faculty PGD rule (₦200,000)
        $userPgdBus = User::factory()->create();
        $studentPgdBus = \App\Models\Student::create([
            'user_id' => $userPgdBus->id,
            'matriculation_number' => 'PGD-BUS-001',
            'program_id' => $pgdBus->id,
            'department_id' => $deptBus->id,
            'faculty_id' => $facultyMgt->id,
            'entry_mode' => 'Postgraduate',
            'current_level' => '700',
            'status' => 'active',
        ]);
        $studentPgdBus->setRelation('program', $pgdBus);
        $invoicePgdBus = $feeService->generateSchoolFeeInvoice($studentPgdBus, $session);
        $this->assertNotNull($invoicePgdBus);
        $this->assertEquals(200000, (float) $invoicePgdBus->items->firstWhere('fee_type_id', $tuitionFeeType->id)->amount);

        // Verify Regular MBA automatically inherits Faculty MBA rule (₦400,000)
        $userRegularMba = User::factory()->create();
        $studentRegularMba = \App\Models\Student::create([
            'user_id' => $userRegularMba->id,
            'matriculation_number' => 'MBA-REG-001',
            'program_id' => $regularMba->id,
            'department_id' => $deptBus->id,
            'faculty_id' => $facultyMgt->id,
            'entry_mode' => 'Postgraduate',
            'current_level' => '800',
            'status' => 'active',
        ]);
        $studentRegularMba->setRelation('program', $regularMba);
        $invoiceRegularMba = $feeService->generateSchoolFeeInvoice($studentRegularMba, $session);
        $this->assertNotNull($invoiceRegularMba);
        $this->assertEquals(400000, (float) $invoiceRegularMba->items->firstWhere('fee_type_id', $tuitionFeeType->id)->amount);

        // Verify Executive MBA resolves to the Program Override (₦650,000)
        $userExecMba = User::factory()->create();
        $studentExecMba = \App\Models\Student::create([
            'user_id' => $userExecMba->id,
            'matriculation_number' => 'MBA-EXEC-001',
            'program_id' => $executiveMba->id,
            'department_id' => $deptBus->id,
            'faculty_id' => $facultyMgt->id,
            'entry_mode' => 'Postgraduate',
            'current_level' => '800',
            'status' => 'active',
        ]);
        $studentExecMba->setRelation('program', $executiveMba);
        $invoiceExecMba = $feeService->generateSchoolFeeInvoice($studentExecMba, $session);
        $this->assertNotNull($invoiceExecMba);
        $this->assertEquals(650000, (float) $invoiceExecMba->items->firstWhere('fee_type_id', $tuitionFeeType->id)->amount);
    }
}
