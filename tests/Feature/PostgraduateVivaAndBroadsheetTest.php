<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\PostgraduateExaminer;
use App\Models\PostgraduateResearchProject;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Postgraduate\PostgraduateVivaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PostgraduateVivaAndBroadsheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureStudentProfileCompleted::class);
    }

    protected function setupVivaEnvironment(): array
    {
        $faculty = Faculty::create(['name' => 'Faculty of Basic Medical Sciences', 'code' => 'BMS']);
        $dept = Department::create(['name' => 'Biochemistry', 'code' => 'BCH', 'faculty_id' => $faculty->id]);
        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);
        $semester = Semester::create(['name' => 'First Semester', 'session_id' => $session->id, 'is_current' => true]);

        $prog = Programme::create([
            'name' => 'Ph.D Biochemistry',
            'department_id' => $dept->id,
            'type' => 'PHD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 3,
        ]);

        $course = Course::create([
            'code' => 'BCH990',
            'title' => 'Doctoral Dissertation & Viva Voce Defense',
            'units' => 12,
            'level' => 900,
            'semester' => '1',
            'course_type' => 'academic_dissertation',
            'department_id' => $dept->id,
        ]);

        $candidateUser = User::factory()->create(['name' => 'Dr. Ngozi Eze', 'email' => 'ngozi@pg.test']);
        $student = Student::create([
            'user_id' => $candidateUser->id,
            'matriculation_number' => 'PG/2026/BCH/005',
            'spgs_file_number' => 'SPGS/BMS/2026/005',
            'program_id' => $prog->id,
            'current_level' => 900,
            'academic_stream' => 'PG',
        ]);

        $reg = CourseRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'semester_id' => $semester->id,
            'ca_score' => 0,
            'exam_score' => 0,
            'score' => 0,
        ]);

        $project = PostgraduateResearchProject::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'course_id' => $course->id,
            'course_registration_id' => $reg->id,
            'pathway_type' => 'academic_dissertation',
            'title' => 'Structural Characterization of Bioactive Peptides in Antimalarial Phytotherapy',
            'research_area' => 'Phytomedicine & Proteomics',
            'status' => 'in_progress',
        ]);

        $adminUser = User::factory()->create(['name' => 'SPGS Exams Officer', 'email' => 'spgs-exams@univ.test']);
        Permission::findOrCreate('access_admin_dashboard');
        Permission::findOrCreate('view_results');
        $adminUser->givePermissionTo(['access_admin_dashboard', 'view_results']);

        return [$candidateUser, $student, $project, $adminUser, $reg];
    }

    public function test_admin_can_appoint_examiners_and_schedule_viva_defense(): void
    {
        [$candidateUser, $student, $project, $adminUser, $reg] = $this->setupVivaEnvironment();

        // 1. Add External Examiner
        $vivaService = app(PostgraduateVivaService::class);
        $examiner = $vivaService->addExaminer($project, [
            'examiner_type' => 'external_examiner',
            'title' => 'Prof.',
            'full_name' => 'Folashade B. Ogundipe',
            'institution_or_department' => 'University of Ibadan',
            'email' => 'fogundipe@ui.edu.ng',
            'phone' => '+2348030001122',
            'specialization' => 'Molecular Proteomics',
            'draft_dispatched_at' => now()->subDays(14)->toDateString(),
            'dispatch_tracking_ref' => 'DHL-UI-SPGS-2026-99',
        ]);

        $this->assertDatabaseHas('postgraduate_examiners', [
            'project_id' => $project->id,
            'full_name' => 'Folashade B. Ogundipe',
            'institution_or_department' => 'University of Ibadan',
        ]);

        // 2. Schedule Oral Defense
        $defenseDateTime = now()->addDays(7)->format('Y-m-d H:i:s');
        $this->actingAs($adminUser)->put(route('admin.postgraduate.research.defense.schedule', $project->id), [
            'defense_date' => $defenseDateTime,
            'defense_venue' => 'SPGS Senate Chambers & Zoom Room 101',
            'external_examiner_name' => 'Prof. Folashade B. Ogundipe',
        ])->assertSessionHas('success');

        $this->assertEquals('scheduled', $project->fresh()->defense_status);
        $this->assertEquals('SPGS Senate Chambers & Zoom Room 101', $project->fresh()->defense_venue);
    }

    public function test_viva_verdict_syncs_with_broadsheet_and_calculates_pg_grade(): void
    {
        [$candidateUser, $student, $project, $adminUser, $reg] = $this->setupVivaEnvironment();

        // Record oral defense score of 78.5% (Grade A) with minor corrections
        $response = $this->actingAs($adminUser)->post(route('admin.postgraduate.research.defense.verdict', $project->id), [
            'final_score' => 78.5,
            'defense_status' => 'passed_minor_corrections',
            'defense_panel_notes' => 'Exceptional defense. Candidate must incorporate NMR spectra in Appendix C before hardbound submission.',
            'repository_url' => 'https://repository.univ.edu/theses/2026/phd-bch-005',
        ]);

        $response->assertSessionHas('success');

        $updatedProject = $project->fresh();
        $this->assertEquals(78.5, $updatedProject->final_score);
        $this->assertEquals('A', $updatedProject->final_grade);
        $this->assertEquals('passed_minor_corrections', $updatedProject->defense_status);
        $this->assertEquals('completed', $updatedProject->status);

        // CourseRegistration automatically updated for SPGS broadsheet
        $updatedReg = $reg->fresh();
        $this->assertEquals(78.5, $updatedReg->exam_score);
        $this->assertEquals('A', $updatedReg->grade);
        $this->assertEquals(5.0, (float) $updatedReg->grade_point);
    }
}
