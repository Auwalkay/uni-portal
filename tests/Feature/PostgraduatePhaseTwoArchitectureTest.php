<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeConfiguration;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AcademicCacheService;
use App\Services\Finance\FeeService;
use App\Services\GradingService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostgraduatePhaseTwoArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AcademicCacheService::clearAll();
    }

    /**
     * Test NUC Postgraduate grading scale (50% pass mark, 3.00 min CGPA, Ph.D progression)
     * compared with Undergraduate grading scale (40% pass mark).
     */
    public function test_nuc_postgraduate_grading_benchmark_and_phd_progression()
    {
        $gradingService = app(GradingService::class);

        $faculty = Faculty::create(['name' => 'Management Sciences', 'code' => 'FMS']);
        $dept = Department::create(['name' => 'Business Administration', 'code' => 'BUS', 'faculty_id' => $faculty->id]);

        $ugCourse = Course::create([
            'code' => 'BUS 101',
            'title' => 'Introduction to Business',
            'units' => 3,
            'level' => 100,
            'semester' => '1',
            'department_id' => $dept->id,
        ]);

        $pgCourse = Course::create([
            'code' => 'MBA 801',
            'title' => 'Corporate Strategy & Governance',
            'units' => 3,
            'level' => 800,
            'semester' => '1',
            'course_type' => 'case_study_seminar',
            'department_id' => $dept->id,
        ]);

        // 1. Score of 48% -> Grade D (2.0) in UG, but Grade F (0.0) in PG
        $ug48 = $gradingService->calculate(48, $ugCourse);
        $this->assertEquals('D', $ug48['grade']);
        $this->assertEquals(2.0, $ug48['point']);

        $pg48 = $gradingService->calculate(48, $pgCourse);
        $this->assertEquals('F', $pg48['grade']);
        $this->assertEquals(0.0, $pg48['point']);

        // 2. Score of 42% -> Grade E (1.0) in UG, Grade F (0.0) in PG
        $ug42 = $gradingService->calculate(42, $ugCourse);
        $this->assertEquals('E', $ug42['grade']);
        $this->assertEquals(1.0, $ug42['point']);

        $pg42 = $gradingService->calculate(42, $pgCourse);
        $this->assertEquals('F', $pg42['grade']);
        $this->assertEquals(0.0, $pg42['point']);

        // 3. Score of 52% -> Grade C (3.0) in PG (Pass)
        $pg52 = $gradingService->calculate(52, $pgCourse);
        $this->assertEquals('C', $pg52['grade']);
        $this->assertEquals(3.0, $pg52['point']);

        // 4. Pass mark & Graduation CGPA thresholds
        $this->assertEquals(40, $gradingService->getPassMark($ugCourse));
        $this->assertEquals(50, $gradingService->getPassMark($pgCourse));
        $this->assertEquals(1.00, $gradingService->getMinimumGraduationCgpa('UG'));
        $this->assertEquals(3.00, $gradingService->getMinimumGraduationCgpa('PG'));

        $this->assertFalse($gradingService->meetsGraduationRequirement(2.85, 'PG'));
        $this->assertTrue($gradingService->meetsGraduationRequirement(3.00, 'PG'));

        // 5. Ph.D Progression evaluation from Master's CGPA
        $directPhd = $gradingService->evaluatePhdProgression(3.65);
        $this->assertTrue($directPhd['eligible_for_phd']);
        $this->assertFalse($directPhd['requires_mphil_conversion']);

        $mphilRoute = $gradingService->evaluatePhdProgression(3.25);
        $this->assertFalse($mphilRoute['eligible_for_phd']);
        $this->assertTrue($mphilRoute['requires_mphil_conversion']);

        $ineligible = $gradingService->evaluatePhdProgression(2.70);
        $this->assertFalse($ineligible['eligible_for_phd']);
        $this->assertFalse($ineligible['requires_mphil_conversion']);
    }

    /**
     * Test ResultController grades PG courses using the 50% pass benchmark and supports
     * flexible CA/Presentation weights for case study / dissertation course types.
     */
    public function test_result_controller_applies_pg_grading_and_course_type_weights()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $faculty = Faculty::create(['name' => 'Computing', 'code' => 'FOC']);
        $dept = Department::create(['name' => 'Information Management', 'code' => 'INF', 'faculty_id' => $faculty->id]);

        $mimProgram = Programme::create([
            'name' => 'Master of Information Management',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'duration' => 2,
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
        $tri1 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 1', 'is_current' => true]);

        $caseStudyCourse = Course::create([
            'code' => 'MIM 802',
            'title' => 'Enterprise Architecture Case Seminar',
            'units' => 3,
            'level' => 800,
            'semester' => '1',
            'course_type' => 'case_study_seminar',
            'department_id' => $dept->id,
            'programme_id' => $mimProgram->id,
        ]);

        $student1User = User::factory()->create(['name' => 'Student One']);
        $student1 = Student::create([
            'user_id' => $student1User->id,
            'matriculation_number' => 'MIU/MIM/2026/001',
            'program_id' => $mimProgram->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
            'entry_mode' => 'Postgraduate',
        ]);

        $student2User = User::factory()->create(['name' => 'Student Two']);
        $student2 = Student::create([
            'user_id' => $student2User->id,
            'matriculation_number' => 'MIU/MIM/2026/002',
            'program_id' => $mimProgram->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
            'entry_mode' => 'Postgraduate',
        ]);

        $reg1 = CourseRegistration::create([
            'student_id' => $student1->id,
            'course_id' => $caseStudyCourse->id,
            'session_id' => $pgSession->id,
            'semester_id' => $tri1->id,
        ]);

        $reg2 = CourseRegistration::create([
            'student_id' => $student2->id,
            'course_id' => $caseStudyCourse->id,
            'session_id' => $pgSession->id,
            'semester_id' => $tri1->id,
        ]);

        // Submit scores:
        // Student 1: 48/50 Presentation (CA) + 32/50 Case Report (Exam) = 80 (Grade A, 5.0)
        // Student 2: 20/50 Presentation (CA) + 28/50 Case Report (Exam) = 48 (Grade F, 0.0 under PG rules!)
        $response = $this->post(route('admin.results.update', $caseStudyCourse->id), [
            'scores' => [
                [
                    'id' => $reg1->id,
                    'ca_score' => 48,
                    'exam_score' => 32,
                    'is_absent' => false,
                ],
                [
                    'id' => $reg2->id,
                    'ca_score' => 20,
                    'exam_score' => 28,
                    'is_absent' => false,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('course_registrations', [
            'id' => $reg1->id,
            'score' => 80,
            'grade' => 'A',
            'grade_point' => 5.0,
        ]);

        $this->assertDatabaseHas('course_registrations', [
            'id' => $reg2->id,
            'score' => 48,
            'grade' => 'F',
            'grade_point' => 0.0,
        ]);
    }

    /**
     * Test Dual-Mode Fee Structure:
     * - Model A (Composite Term): MBA student receives flat all-inclusive tuition invoice.
     * - Model B (Unbundled Milestone): M.Sc student receives coursework tuition invoice initially,
     *   and separate milestone invoices (Bench Fee, Viva Voce Defense Fee) only when triggered.
     */
    public function test_dual_mode_fee_structure_composite_vs_unbundled_milestones()
    {
        SystemSetting::set('admin_charge_enabled', false);

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'FSC']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);

        $pgSession = Session::create([
            'name' => '2025/2026 PG Session',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'is_current' => true,
            'school_fee_payment_enabled' => true,
        ]);

        $mscProgram = Programme::create([
            'name' => 'M.Sc. Computer Science',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'billing_model' => 'unbundled_milestone',
            'duration' => 2,
        ]);

        $tuitionType = FeeType::create(['name' => 'PG Coursework Tuition', 'slug' => 'pg-coursework-tuition', 'is_one_time' => false]);
        $benchType = FeeType::create(['name' => 'PG Research Bench Fee', 'slug' => 'pg-research-bench-fee', 'is_one_time' => true]);
        $defenseType = FeeType::create(['name' => 'External Viva Voce Defense Fee', 'slug' => 'external-viva-voce-defense-fee', 'is_one_time' => true]);

        // Configure Coursework Tuition (Standard compulsory school fee)
        FeeConfiguration::create([
            'fee_type_id' => $tuitionType->id,
            'session_id' => $pgSession->id,
            'program_id' => $mscProgram->id,
            'amount' => 350000,
            'is_compulsory' => true,
            'is_milestone_fee' => false,
        ]);

        // Configure Research Bench Milestone Fee
        FeeConfiguration::create([
            'fee_type_id' => $benchType->id,
            'session_id' => $pgSession->id,
            'program_id' => $mscProgram->id,
            'amount' => 150000,
            'is_compulsory' => true,
            'is_milestone_fee' => true,
            'milestone_type' => 'bench_fee',
        ]);

        // Configure External Defense Milestone Fee
        FeeConfiguration::create([
            'fee_type_id' => $defenseType->id,
            'session_id' => $pgSession->id,
            'program_id' => $mscProgram->id,
            'amount' => 200000,
            'is_compulsory' => true,
            'is_milestone_fee' => true,
            'milestone_type' => 'defense_fee',
        ]);

        $user = User::factory()->create();
        $mscStudent = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'MIU/MSC/2026/001',
            'program_id' => $mscProgram->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
            'entry_mode' => 'Postgraduate',
        ]);

        $feeService = app(FeeService::class);

        // 1. Initial School Fee Invoice must ONLY charge Coursework Tuition (350,000), NOT Bench or Defense fees
        $schoolFeeInvoice = $feeService->generateSchoolFeeInvoice($mscStudent, $pgSession);
        $this->assertNotNull($schoolFeeInvoice);
        $this->assertEquals(350000, (float) $schoolFeeInvoice->amount);
        $this->assertEquals(1, $schoolFeeInvoice->items()->count());

        // 2. Both Bench Fee and Defense Fee should be available as unbilled milestones
        $availableMilestones = $feeService->getAvailableMilestoneFees($mscStudent, $pgSession);
        $this->assertCount(2, $availableMilestones);

        // 3. Trigger Research Bench Fee Milestone Invoice
        $benchInvoice = $feeService->generateMilestoneFeeInvoice($mscStudent, $pgSession, 'bench_fee');
        $this->assertNotNull($benchInvoice);
        $this->assertEquals('milestone_fee', $benchInvoice->type);
        $this->assertEquals(150000, (float) $benchInvoice->amount);

        // 4. Calling generateMilestoneFeeInvoice again for bench_fee is idempotent (returns same invoice)
        $benchInvoiceDuplicate = $feeService->generateMilestoneFeeInvoice($mscStudent, $pgSession, 'bench_fee');
        $this->assertEquals($benchInvoice->id, $benchInvoiceDuplicate->id);

        // 5. Available milestones now only has defense_fee remaining
        $remainingMilestones = $feeService->getAvailableMilestoneFees($mscStudent, $pgSession);
        $this->assertCount(1, $remainingMilestones);
        $this->assertEquals('defense_fee', $remainingMilestones->first()->milestone_type);
    }

    /**
     * Test PG Trimester Student can register 700/800/900-level courses across all 3 Trimesters
     * while a UG 2-Semester session is concurrently active.
     */
    public function test_pg_trimester_course_registration_across_three_trimesters()
    {
        $faculty = Faculty::create(['name' => 'Management Sciences', 'code' => 'FMS', 'is_active' => true]);
        $dept = Department::create(['name' => 'Business Administration', 'code' => 'BUS', 'faculty_id' => $faculty->id, 'is_active' => true]);

        // Active UG Session
        $ugSession = Session::create([
            'name' => '2025/2026 UG Session',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'is_current' => true,
            'registration_enabled' => true,
        ]);
        Semester::create(['session_id' => $ugSession->id, 'name' => 'First Semester', 'is_current' => true]);

        // Concurrent Active PG Trimester Session
        $pgSession = Session::create([
            'name' => '2025/2026 Executive MBA Session',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'is_current' => true,
            'registration_enabled' => true,
        ]);
        $tri1 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 1', 'is_current' => true]);
        $tri2 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 2', 'is_current' => false]);
        $tri3 = Semester::create(['session_id' => $pgSession->id, 'name' => 'Trimester 3', 'is_current' => false]);

        $mbaProgram = Programme::create([
            'name' => 'Executive MBA',
            'department_id' => $dept->id,
            'type' => 'PG',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'billing_model' => 'composite_term',
            'duration' => 2,
            'is_active' => true,
        ]);

        $c1 = Course::create([
            'code' => 'MBA 801',
            'title' => 'Executive Leadership',
            'units' => 3,
            'level' => 800,
            'semester' => '1',
            'course_type' => 'lecture_exam',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);
        $c2 = Course::create([
            'code' => 'MBA 802',
            'title' => 'Strategic Marketing Case Seminar',
            'units' => 3,
            'level' => 800,
            'semester' => '2',
            'course_type' => 'case_study_seminar',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);
        $c3 = Course::create([
            'code' => 'MBA 899',
            'title' => 'Executive Capstone Consulting Project',
            'units' => 6,
            'level' => 800,
            'semester' => '3',
            'course_type' => 'capstone_project',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'MBA/2026/001',
            'program_id' => $mbaProgram->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
            'entry_mode' => 'Postgraduate',
        ]);

        // Mark PG school fee paid for this session
        Invoice::create([
            'user_id' => $user->id,
            'session_id' => $pgSession->id,
            'type' => 'school_fee',
            'reference' => 'SCH-MBA-001',
            'invoice_number' => 'INV-MBA-001',
            'amount' => 450000,
            'paid_amount' => 450000,
            'status' => 'paid',
            'due_date' => now()->addMonth(),
        ]);

        $this->actingAs($user);
        $this->withoutMiddleware(\App\Http\Middleware\EnsureStudentProfileCompleted::class);

        $response = $this->post(route('student.courses.store'), [
            'courses' => [$c1->id, $c2->id, $c3->id],
        ]);

        $response->assertRedirect(route('student.courses.index'));

        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $c1->id,
            'session_id' => $pgSession->id,
            'semester_id' => $tri1->id,
        ]);
        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $c2->id,
            'session_id' => $pgSession->id,
            'semester_id' => $tri2->id,
        ]);
        $this->assertDatabaseHas('course_registrations', [
            'student_id' => $student->id,
            'course_id' => $c3->id,
            'session_id' => $pgSession->id,
            'semester_id' => $tri3->id,
        ]);
    }
}
