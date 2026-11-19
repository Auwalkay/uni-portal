<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeConfiguration;
use App\Models\FeeType;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicProgrammesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_programmes_page_displays_active_programmes_and_resolved_fees(): void
    {
        $faculty = Faculty::create(['name' => 'Computing', 'code' => 'CMP', 'is_active' => true]);
        $department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);

        $ugProgramme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'department_id' => $department->id,
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'duration' => 4,
            'is_active' => true,
        ]);

        $pgProgramme = Programme::create([
            'name' => 'M.Sc Computer Science',
            'department_id' => $department->id,
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 2,
            'is_active' => true,
        ]);

        Programme::create([
            'name' => 'Archived Programme',
            'department_id' => $department->id,
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'is_active' => false,
        ]);

        $ugSession = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $pgSession = Session::create([
            'name' => '2026/2027 PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-08-31',
            'is_current' => true,
        ]);

        $tuition = FeeType::create([
            'name' => 'Tuition Fee',
            'slug' => 'tuition-fee',
            'is_active' => true,
            'is_one_time' => false,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $tuition->id,
            'session_id' => $ugSession->id,
            'program_id' => $ugProgramme->id,
            'level' => '100',
            'amount' => 450000,
            'is_compulsory' => true,
            'is_milestone_fee' => false,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $tuition->id,
            'session_id' => $ugSession->id,
            'program_id' => $ugProgramme->id,
            'level' => '200',
            'entry_mode' => 'Direct Entry',
            'amount' => 500000,
            'is_compulsory' => true,
            'is_milestone_fee' => false,
        ]);

        $transferFee = FeeType::create([
            'name' => 'Inter-University Transfer Fee',
            'slug' => 'transfer-fee',
            'is_active' => true,
            'is_one_time' => true,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $transferFee->id,
            'session_id' => $ugSession->id,
            'level' => '100',
            'entry_mode' => 'Transfer',
            'amount' => 150000,
            'is_compulsory' => true,
            'is_milestone_fee' => false,
        ]);

        FeeConfiguration::create([
            'fee_type_id' => $tuition->id,
            'session_id' => $pgSession->id,
            'program_id' => $pgProgramme->id,
            'level' => '700',
            'amount' => 650000,
            'is_compulsory' => true,
            'is_milestone_fee' => false,
        ]);

        Cache::flush();

        $response = $this->get('/programmes');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Programmes')
            ->has('programmes', 2)
            ->where('ugSessionName', '2026/2027')
            ->where('pgSessionName', '2026/2027 PG')
            ->where('programmes.0.schedules_by_mode.UTME.total_fee', 450000)
            ->where('programmes.0.schedules_by_mode.DE.total_fee', 500000)
            ->where('programmes.0.schedules_by_mode.Transfer.total_fee', 650000)
        );

        // Verify DE alias ('DE' student matches 'Direct Entry' FeeConfiguration) and Transfer invoice generation
        $deUser = \App\Models\User::factory()->create();
        $deStudent = \App\Models\Student::create([
            'user_id' => $deUser->id,
            'department_id' => $department->id,
            'faculty_id' => $faculty->id,
            'program_id' => $ugProgramme->id,
            'matriculation_number' => 'MIUDE26001',
            'current_level' => 200,
            'entry_mode' => 'DE',
            'status' => 'active',
        ]);

        $feeService = app(\App\Services\Finance\FeeService::class);
        $deInvoice = $feeService->generateSchoolFeeInvoice($deStudent, $ugSession);
        $this->assertNotNull($deInvoice);
        $this->assertEquals(750000, (float) $deInvoice->amount);

        $transferUser = \App\Models\User::factory()->create();
        $transferStudent = \App\Models\Student::create([
            'user_id' => $transferUser->id,
            'department_id' => $department->id,
            'faculty_id' => $faculty->id,
            'program_id' => $ugProgramme->id,
            'matriculation_number' => 'MIUTR26301',
            'current_level' => 300,
            'entry_mode' => 'Transfer',
            'status' => 'active',
        ]);

        $transferInvoice = $feeService->generateSchoolFeeInvoice($transferStudent, $ugSession);
        $this->assertNotNull($transferInvoice);
        $this->assertEquals(400000, (float) $transferInvoice->amount);
    }
}
