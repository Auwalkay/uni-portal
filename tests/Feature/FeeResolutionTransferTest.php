<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdmissionController;
use App\Http\Controllers\Student\ProfileController;
use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeConfiguration;
use App\Models\FeeType;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeResolutionTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_utme_student_and_applicant_never_get_transfer_fees()
    {
        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $tuitionFeeType = FeeType::create([
            'name' => 'Tuition Fee',
            'slug' => 'tuition-fee',
            'is_one_time' => false,
        ]);

        $transferFeeType = FeeType::create([
            'name' => 'Transfer Fees',
            'slug' => 'transfer-fees',
            'is_one_time' => true,
        ]);

        $faculty = Faculty::create(['name' => 'Faculty of Allied Health Sciences', 'code' => 'FAHS']);
        $dept = Department::create(['name' => 'Nursing Science', 'code' => 'NSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create([
            'name' => 'B.N.Sc. Nursing Science',
            'department_id' => $dept->id,
            'type' => 'UG',
            'duration' => 4,
        ]);

        // Standard Tuition rule
        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $tuitionFeeType->id,
            'amount' => 1250000,
            'is_compulsory' => true,
            'faculty_id' => null,
            'department_id' => null,
            'program_id' => null,
            'level' => null,
            'entry_mode' => null,
        ]);

        // Transfer Fee rule with entry_mode = 'Transfer'
        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $transferFeeType->id,
            'amount' => 300000,
            'is_compulsory' => true,
            'faculty_id' => null,
            'department_id' => null,
            'program_id' => null,
            'level' => null,
            'entry_mode' => 'Transfer',
        ]);

        $user = User::factory()->create(['name' => 'OBANSA HUSSEINA MOHAMMED']);

        $applicant = Applicant::create([
            'user_id' => $user->id,
            'application_number' => 'MIUSTD26NSC1002',
            'program_choice_1' => $prog->id,
            'application_mode' => 'UTME',
            'status' => 'admitted',
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'MIUSTD26NSC1002',
            'program_id' => $prog->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 100,
            'entry_mode' => 'UTME',
            'admitted_session_id' => $session->id,
            'status' => 'active',
        ]);

        // Check AdmissionController
        $admController = new AdmissionController();
        $refAdm = new \ReflectionMethod($admController, 'calculateEstimatedFeesForApplicant');
        $refAdm->setAccessible(true);
        $feesAdm = $refAdm->invoke($admController, $applicant, $session);

        $this->assertEquals(1250000, $feesAdm['tuition']);
        $this->assertEquals(0, $feesAdm['one_time_fees']);
        $this->assertEmpty($feesAdm['one_time_fees_list']);

        // Check ProfileController for applicant
        $profController = new ProfileController();
        $refProfApplicant = new \ReflectionMethod($profController, 'calculateEstimatedFeesForApplicant');
        $refProfApplicant->setAccessible(true);
        $feesProfApplicant = $refProfApplicant->invoke($profController, $applicant, $session);

        $this->assertEquals(1250000, $feesProfApplicant['tuition']);
        $this->assertEquals(0, $feesProfApplicant['one_time_fees']);
        $this->assertEmpty($feesProfApplicant['one_time_fees_list']);

        // Check ProfileController for student
        $refProfStudent = new \ReflectionMethod($profController, 'calculateEstimatedFees');
        $refProfStudent->setAccessible(true);
        $feesProfStudent = $refProfStudent->invoke($profController, $student);

        $this->assertEquals(1250000, $feesProfStudent['tuition']);
        $this->assertEquals(0, $feesProfStudent['one_time_fees']);
        $this->assertEmpty($feesProfStudent['one_time_fees_list']);
    }

    public function test_transfer_student_gets_transfer_fee()
    {
        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $tuitionFeeType = FeeType::create([
            'name' => 'Tuition Fee',
            'slug' => 'tuition-fee',
            'is_one_time' => false,
        ]);

        $transferFeeType = FeeType::create([
            'name' => 'Transfer Fees',
            'slug' => 'transfer-fees',
            'is_one_time' => true,
        ]);

        $faculty = Faculty::create(['name' => 'Faculty of Allied Health Sciences', 'code' => 'FAHS']);
        $dept = Department::create(['name' => 'Nursing Science', 'code' => 'NSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create([
            'name' => 'B.N.Sc. Nursing Science',
            'department_id' => $dept->id,
            'type' => 'UG',
            'duration' => 4,
        ]);

        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $tuitionFeeType->id,
            'amount' => 1250000,
            'is_compulsory' => true,
            'faculty_id' => null,
            'department_id' => null,
            'program_id' => null,
            'level' => null,
            'entry_mode' => null,
        ]);

        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $transferFeeType->id,
            'amount' => 300000,
            'is_compulsory' => true,
            'faculty_id' => null,
            'department_id' => null,
            'program_id' => null,
            'level' => null,
            'entry_mode' => 'Transfer',
        ]);

        $transferUser = User::factory()->create(['name' => 'Transfer Student']);
        $transferStudent = Student::create([
            'user_id' => $transferUser->id,
            'matriculation_number' => 'MIUSTD26TRN1001',
            'program_id' => $prog->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 200,
            'entry_mode' => 'Transfer',
            'admitted_session_id' => $session->id,
            'status' => 'active',
        ]);

        $profController = new ProfileController();
        $refProfStudent = new \ReflectionMethod($profController, 'calculateEstimatedFees');
        $refProfStudent->setAccessible(true);
        $fees = $refProfStudent->invoke($profController, $transferStudent);

        $this->assertEquals(1250000, $fees['tuition']);
        $this->assertEquals(300000, $fees['one_time_fees']);
        $this->assertCount(1, $fees['one_time_fees_list']);
        $this->assertEquals('Transfer Fees', $fees['one_time_fees_list'][0]['name']);
    }

    public function test_even_if_transfer_fee_rule_has_null_entry_mode_it_does_not_charge_utme()
    {
        $session = Session::create([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $transferFeeType = FeeType::create([
            'name' => 'Transfer Fees',
            'slug' => 'transfer-fees',
            'is_one_time' => true,
        ]);

        // Admin accidentally created Transfer Fee rule without selecting Transfer (entry_mode = null)
        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $transferFeeType->id,
            'amount' => 300000,
            'is_compulsory' => true,
            'faculty_id' => null,
            'department_id' => null,
            'program_id' => null,
            'level' => null,
            'entry_mode' => null,
        ]);

        $faculty = Faculty::create(['name' => 'Faculty of Allied Health Sciences', 'code' => 'FAHS']);
        $dept = Department::create(['name' => 'Nursing Science', 'code' => 'NSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create([
            'name' => 'B.N.Sc. Nursing Science',
            'department_id' => $dept->id,
            'type' => 'UG',
            'duration' => 4,
        ]);

        $user = User::factory()->create(['name' => 'UTME Student']);
        $applicant = Applicant::create([
            'user_id' => $user->id,
            'application_number' => 'UTME001',
            'program_choice_1' => $prog->id,
            'application_mode' => 'UTME',
            'status' => 'admitted',
        ]);

        $admController = new AdmissionController();
        $refAdm = new \ReflectionMethod($admController, 'calculateEstimatedFeesForApplicant');
        $refAdm->setAccessible(true);
        $feesAdm = $refAdm->invoke($admController, $applicant, $session);

        $this->assertEquals(0, $feesAdm['one_time_fees']);
        $this->assertEmpty($feesAdm['one_time_fees_list']);
    }
}
