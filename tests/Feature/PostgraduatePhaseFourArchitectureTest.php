<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureStudentProfileCompleted;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\PostgraduateResearchProject;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Postgraduate\PostgraduateResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgraduatePhaseFourArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $accessStudent = Permission::firstOrCreate(['name' => 'access_student_portal', 'guard_name' => 'web']);
        $accessAdmin = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $viewResults = Permission::firstOrCreate(['name' => 'view_results', 'guard_name' => 'web']);

        $studentRole->givePermissionTo($accessStudent);
        $adminRole->givePermissionTo([$accessAdmin, $viewResults]);
    }

    public function test_culmination_pathways_and_milestone_templates_by_programme_tier(): void
    {
        $service = app(PostgraduateResearchService::class);
        $faculty = Faculty::create(['name' => 'School of Postgraduate Studies', 'code' => 'SPGS', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Management & Computing', 'code' => 'MGC', 'is_active' => true]);

        $pgdProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'PGD Information Technology',
            'type' => 'PGD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 1,
            'is_active' => true,
        ]);

        $mbaProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'Master of Business Administration (MBA)',
            'type' => 'MBA',
            'academic_stream' => 'PG',
            'calendar_type' => 'trimester',
            'duration' => 2,
            'is_active' => true,
        ]);

        $phdProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'Ph.D Computer Science',
            'type' => 'PHD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 3,
            'is_active' => true,
        ]);

        $pgdStudent = Student::create([
            'user_id' => User::factory()->create()->id,
            'matriculation_number' => 'PGD/2026/001',
            'program_id' => $pgdProg->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 700,
        ]);

        $mbaStudent = Student::create([
            'user_id' => User::factory()->create()->id,
            'matriculation_number' => 'MBA/2026/001',
            'program_id' => $mbaProg->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
        ]);

        $phdStudent = Student::create([
            'user_id' => User::factory()->create()->id,
            'matriculation_number' => 'PHD/2026/001',
            'program_id' => $phdProg->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 900,
        ]);

        $pgdProject = $service->createOrUpdateProject($pgdStudent, ['title' => 'PGD Network Security Audit']);
        $this->assertSame('pgd_project', $pgdProject->pathway_type);
        $this->assertCount(4, $pgdProject->milestones);

        $mbaProject = $service->createOrUpdateProject($mbaStudent, ['title' => 'Fintech Growth Strategy Case Study']);
        $this->assertSame('capstone_consulting', $mbaProject->pathway_type);
        $this->assertCount(5, $mbaProject->milestones);

        $phdProject = $service->createOrUpdateProject($phdStudent, ['title' => 'Quantum-Safe Cryptography in Zero-Trust Networks']);
        $this->assertSame('academic_dissertation', $phdProject->pathway_type);
        $this->assertCount(6, $phdProject->milestones);
    }

    public function test_supervisor_allocation_plagiarism_threshold_viva_verdict_and_broadsheet_sync(): void
    {
        $pgSession = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);
        $sem = Semester::create(['session_id' => $pgSession->id, 'name' => 'Second Semester', 'is_current' => true]);

        $faculty = Faculty::create(['name' => 'Faculty of Computing', 'code' => 'FOC', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC', 'is_active' => true]);

        $mscProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 2,
            'is_active' => true,
        ]);

        $thesisCourse = Course::create([
            'code' => 'CSC 899',
            'title' => 'M.Sc Dissertation',
            'units' => 6,
            'level' => 800,
            'semester' => '2',
            'course_type' => 'academic_dissertation',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);

        $studentUser = User::factory()->create(['name' => 'Zainab Usman']);
        $studentUser->assignRole('student');

        $student = Student::create([
            'user_id' => $studentUser->id,
            'matriculation_number' => 'MSC/2026/001',
            'program_id' => $mscProg->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
            'entry_mode' => 'PG',
        ]);

        $courseReg = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $thesisCourse->id,
            'session_id' => $pgSession->id,
            'semester_id' => $sem->id,
        ]);

        // 1. Student registers dissertation topic
        $this->actingAs($studentUser);
        $this->withoutMiddleware(EnsureStudentProfileCompleted::class);

        $this->post(route('student.research.topic.store'), [
            'title' => 'Explainable Deep Reinforcement Learning for Autonomous Grid Control',
            'research_area' => 'Artificial Intelligence',
            'abstract' => 'This dissertation investigates interpretable policy gradients...',
        ])->assertSessionHas('success');

        $project = PostgraduateResearchProject::where('student_id', $student->id)->firstOrFail();
        $this->assertSame($courseReg->id, $project->course_registration_id);
        $this->assertSame('topic_proposed', $project->status);

        // 2. Admin allocates Major Supervisor & Co-Supervisor
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $sup1 = Staff::create([
            'user_id' => User::factory()->create(['name' => 'Prof. Musa Ibrahim'])->id,
            'staff_number' => 'STF/001',
            'designation' => 'Professor',
            'department_id' => $dept->id,
        ]);
        $sup2 = Staff::create([
            'user_id' => User::factory()->create(['name' => 'Dr. Halima Sadiq'])->id,
            'staff_number' => 'STF/002',
            'designation' => 'Senior Lecturer',
            'department_id' => $dept->id,
        ]);

        // Duplicate Major and Co-Supervisor should fail
        $this->actingAs($admin)->put(route('admin.postgraduate.research.supervisors', $project->id), [
            'major_supervisor_id' => $sup1->id,
            'co_supervisor_id' => $sup1->id,
        ])->assertSessionHasErrors('co_supervisor_id');

        // Valid supervisor allocation
        $this->actingAs($admin)->put(route('admin.postgraduate.research.supervisors', $project->id), [
            'major_supervisor_id' => $sup1->id,
            'co_supervisor_id' => $sup2->id,
            'external_examiner_name' => 'Prof. Chinedu Okafor',
            'external_examiner_institution' => 'University of Nigeria, Nsukka',
        ])->assertSessionHas('success');

        $project->refresh();
        $this->assertSame('in_progress', $project->status);
        $this->assertSame($sup1->id, $project->major_supervisor_id);
        $this->assertSame($sup2->id, $project->co_supervisor_id);

        // 3. Student submits pre-defense draft milestone: > 20% similarity rejected, <= 20% accepted
        $preDefenseMilestone = $project->milestones()->where('stage', 'pre_defense_draft')->firstOrFail();

        $this->actingAs($studentUser)->post(route('student.research.milestones.submit', $preDefenseMilestone->id), [
            'submission_notes' => 'Draft with high similarity',
            'plagiarism_similarity_index' => 26.5,
        ])->assertSessionHasErrors('plagiarism_similarity_index');

        $this->actingAs($studentUser)->post(route('student.research.milestones.submit', $preDefenseMilestone->id), [
            'submission_notes' => 'Clean Turnitin draft',
            'plagiarism_similarity_index' => 12.4,
        ])->assertSessionHas('success');

        $preDefenseMilestone->refresh();
        $this->assertSame('submitted', $preDefenseMilestone->status);
        $this->assertEquals(12.4, $preDefenseMilestone->plagiarism_similarity_index);

        // 4. Admin approves milestone, schedules Viva Voce, and records final verdict (74% -> Grade A)
        $this->actingAs($admin)->put(route('admin.postgraduate.research.milestones.review', $preDefenseMilestone->id), [
            'status' => 'spgs_cleared',
            'spgs_remarks' => 'Cleared for Viva Voce Defense.',
        ])->assertSessionHas('success');

        $this->actingAs($admin)->put(route('admin.postgraduate.research.defense.schedule', $project->id), [
            'defense_date' => '2027-06-15 10:00:00',
            'defense_venue' => 'SPGS Senate Chamber',
            'external_examiner_name' => 'Prof. Chinedu Okafor',
        ])->assertSessionHas('success');

        $this->actingAs($admin)->post(route('admin.postgraduate.research.defense.verdict', $project->id), [
            'final_score' => 74,
            'defense_status' => 'passed_minor_corrections',
            'defense_panel_notes' => 'Excellent oral defense and empirical validation.',
            'repository_url' => 'https://repository.university.edu.ng/etd/msc-2026-001',
        ])->assertSessionHas('success');

        $project->refresh();
        $courseReg->refresh();

        $this->assertSame('completed', $project->status);
        $this->assertEquals(74.0, $project->final_score);
        $this->assertSame('A', $project->final_grade);
        $this->assertEquals(5.0, $project->final_grade_point);

        // Verify automatic sync with CourseRegistration broadsheet
        $this->assertEquals(74.0, (float) $courseReg->score);
        $this->assertSame('A', $courseReg->grade);
        $this->assertEquals(5.0, (float) $courseReg->grade_point);
    }

    public function test_stream_scoped_promotion_keeps_pg_tier_level_tracks_extension_and_graduates_only_after_defense(): void
    {
        $ugSession1 = Session::create([
            'name' => '2026/2027 UG',
            'type' => 'regular',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ]);
        Semester::create(['session_id' => $ugSession1->id, 'name' => 'First Semester', 'is_current' => true]);

        $pgSession1 = Session::create([
            'name' => '2026/2027 PG',
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);
        $pgSem1 = Semester::create(['session_id' => $pgSession1->id, 'name' => 'First Semester', 'is_current' => true]);

        $pgSession2 = Session::create([
            'name' => '2027/2028 PG',
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2027-10-01',
            'end_date' => '2028-07-31',
            'is_current' => false,
        ]);
        Semester::create(['session_id' => $pgSession2->id, 'name' => 'First Semester', 'is_current' => false]);

        $pgSession3 = Session::create([
            'name' => '2028/2029 PG',
            'type' => 'regular',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2028-10-01',
            'end_date' => '2029-07-31',
            'is_current' => false,
        ]);
        Semester::create(['session_id' => $pgSession3->id, 'name' => 'First Semester', 'is_current' => false]);

        \App\Models\SystemSetting::updateOrCreate(['key' => 'promote_pending_payments'], ['value' => 'true']);

        $faculty = Faculty::create(['name' => 'Faculty of Science', 'code' => 'FSC', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC', 'is_active' => true]);

        $ugProgramme = Programme::create([
            'department_id' => $dept->id,
            'name' => 'B.Sc Computer Science',
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'duration' => 4,
            'is_active' => true,
        ]);

        $mscProgramme = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'billing_model' => 'programme_milestone',
            'min_graduation_cgpa' => 3.00,
            'duration' => 1, // 1-session normal duration so Session 2 triggers extension
            'is_active' => true,
        ]);

        // Configure extension_fee milestone for 800L M.Sc
        $extFeeType = \App\Models\FeeType::create([
            'name' => 'PG Candidacy Extension Fee',
            'slug' => 'pg-extension-fee',
            'is_one_time' => false,
        ]);
        \App\Models\FeeConfiguration::create([
            'fee_type_id' => $extFeeType->id,
            'session_id' => $pgSession2->id,
            'program_id' => $mscProgramme->id,
            'level' => '800',
            'amount' => 75000,
            'is_compulsory' => false,
            'is_milestone_fee' => true,
            'milestone_type' => 'extension_fee',
        ]);

        $ugStudent = Student::create([
            'user_id' => User::factory()->create()->id,
            'matriculation_number' => 'CSC/2026/001',
            'program_id' => $ugProgramme->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 100,
            'program_duration' => 4,
            'status' => 'active',
        ]);

        $pgStudent = Student::create([
            'user_id' => User::factory()->create()->id,
            'matriculation_number' => 'MSC/2026/001',
            'program_id' => $mscProgramme->id,
            'department_id' => $dept->id,
            'faculty_id' => $faculty->id,
            'current_level' => 800,
            'program_duration' => 1,
            'entry_mode' => 'PG',
            'status' => 'active',
        ]);

        \App\Models\PostgraduateStudentProfile::create([
            'student_id' => $pgStudent->id,
            'spgs_file_number' => 'SPGS/2026/0001',
            'study_mode' => 'full_time',
            'sessions_enrolled_count' => 1,
            'candidacy_status' => 'active_coursework',
        ]);

        // 1. Run PromoteStudentsJob for PG Session 2 (2027/2028 PG)
        (new \App\Jobs\Academic\PromoteStudentsJob($pgSession2->id))->handle();

        // UG Session 1 and UG Student should remain untouched!
        $this->assertTrue($ugSession1->fresh()->is_current);
        $this->assertSame(100, (int) $ugStudent->fresh()->current_level);

        // PG Session 2 is now current for PG, and PG student stays at 800L (NOT 900L and NOT graduated because defense isn't completed)
        $this->assertFalse($pgSession1->fresh()->is_current);
        $this->assertTrue($pgSession2->fresh()->is_current);
        $this->assertSame(800, (int) $pgStudent->fresh()->current_level);
        $this->assertSame('active', $pgStudent->fresh()->status);

        // Because sessions_enrolled_count became 2 (> program_duration of 1), candidacy_status is 'extension' and extension_fee invoice was created
        $this->assertSame(2, $pgStudent->pgProfile->fresh()->sessions_enrolled_count);
        $this->assertSame('extension', $pgStudent->pgProfile->fresh()->candidacy_status);
        $this->assertDatabaseHas('invoices', [
            'user_id' => $pgStudent->user_id,
            'session_id' => $pgSession2->id,
            'type' => 'milestone_fee',
            'amount' => 75000,
        ]);

        // 2. Complete Dissertation defense with CGPA >= 3.00, then promote to PG Session 3 -> should graduate!
        $course = Course::create([
            'code' => 'CSC 899',
            'title' => 'M.Sc Dissertation',
            'units' => 6,
            'level' => 800,
            'semester' => '1',
            'course_type' => 'academic_dissertation',
            'department_id' => $dept->id,
            'is_active' => true,
        ]);
        CourseRegistration::create([
            'student_id' => $pgStudent->id,
            'course_id' => $course->id,
            'session_id' => $pgSession2->id,
            'semester_id' => $pgSem1->id,
            'score' => 75,
            'grade' => 'A',
            'grade_point' => 5.0,
        ]);
        PostgraduateResearchProject::create([
            'student_id' => $pgStudent->id,
            'session_id' => $pgSession2->id,
            'pathway_type' => 'academic_dissertation',
            'title' => 'Completed M.Sc Thesis',
            'status' => 'completed',
            'final_score' => 75,
            'final_grade' => 'A',
            'final_grade_point' => 5.0,
        ]);

        (new \App\Jobs\Academic\PromoteStudentsJob($pgSession3->id))->handle();

        $this->assertSame('graduated', $pgStudent->fresh()->status);
        $this->assertSame('graduated', $pgStudent->pgProfile->fresh()->candidacy_status);
    }
}
