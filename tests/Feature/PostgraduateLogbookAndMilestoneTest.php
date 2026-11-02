<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\PostgraduateResearchMilestone;
use App\Models\PostgraduateResearchProject;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Postgraduate\PostgraduateResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PostgraduateLogbookAndMilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureStudentProfileCompleted::class);
    }

    protected function createPgCandidate(): array
    {
        $faculty = Faculty::create(['name' => 'Faculty of Engineering', 'code' => 'ENG']);
        $dept = Department::create(['name' => 'Electrical Engineering', 'code' => 'EEE', 'faculty_id' => $faculty->id]);
        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $prog = Programme::create([
            'name' => 'Ph.D Electrical & Electronics Engineering',
            'department_id' => $dept->id,
            'type' => 'PHD',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 3,
        ]);

        $user = User::factory()->create(['name' => 'Engr. Haruna Bello', 'email' => 'haruna@pg.test']);
        Permission::firstOrCreate(['name' => 'access_student_portal', 'guard_name' => 'web']);
        $user->givePermissionTo('access_student_portal');
        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'PG/2026/PHD/001',
            'spgs_file_number' => 'SPGS/ENG/2026/001',
            'program_id' => $prog->id,
            'current_level' => 900,
            'academic_stream' => 'PG',
        ]);

        $supUser = User::factory()->create(['name' => 'Prof. S. A. Gwandu', 'email' => 'gwandu@univ.test']);
        $supervisor = Staff::create([
            'user_id' => $supUser->id,
            'staff_number' => 'STF/ENG/001',
            'designation' => 'Professor of Power Systems',
            'is_academic' => true,
        ]);

        $project = PostgraduateResearchProject::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'pathway_type' => 'academic_dissertation',
            'title' => 'Optimal Renewable Integration in Smart Microgrids',
            'research_area' => 'Smart Grid Optimization',
            'major_supervisor_id' => $supervisor->id,
            'status' => 'in_progress',
        ]);

        $project->initializeDefaultMilestones();

        return [$user, $student, $supervisor, $project];
    }

    public function test_student_can_log_digital_research_entry(): void
    {
        [$user, $student, $supervisor, $project] = $this->createPgCandidate();

        $response = $this->actingAs($user)->post(route('student.research.logs.store'), [
            'entry_date' => now()->toDateString(),
            'activity_summary' => 'Grid Simulation using MATLAB & OpenDSS',
            'details_of_work_done' => 'Constructed IEEE 33-bus benchmark network and ran power flow simulations under variable PV penetration.',
            'challenges_and_next_steps' => 'Voltage fluctuations observed at buses 18 and 30. Meeting scheduled with supervisor.',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('postgraduate_research_logs', [
            'student_id' => $student->id,
            'project_id' => $project->id,
            'activity_summary' => 'Grid Simulation using MATLAB & OpenDSS',
            'supervisor_status' => 'pending',
        ]);
    }

    public function test_supervisor_can_review_candidate_log_entry(): void
    {
        [$user, $student, $supervisor, $project] = $this->createPgCandidate();

        $researchService = app(PostgraduateResearchService::class);
        $log = $researchService->recordLogEntry($student, [
            'entry_date' => now()->toDateString(),
            'activity_summary' => 'Drafting Chapter 3: Methodology',
            'details_of_work_done' => 'Formulated multi-objective optimization equations using Genetic Algorithms.',
        ]);

        $reviewed = $researchService->reviewLogEntry($log, $supervisor, [
            'supervisor_status' => 'reviewed',
            'supervisor_feedback' => 'Good formulation. Ensure constraints on reactive power reserves are clearly defined.',
        ]);

        $this->assertEquals('reviewed', $reviewed->supervisor_status);
        $this->assertEquals($supervisor->id, $reviewed->reviewed_by_staff_id);
        $this->assertNotNull($reviewed->reviewed_at);
    }

    public function test_milestone_submission_enforces_plagiarism_similarity_benchmark(): void
    {
        [$user, $student, $supervisor, $project] = $this->createPgCandidate();

        $draftMilestone = $project->milestones()->where('stage', 'pre_defense_draft')->firstOrFail();

        // 1. Rejects plagiarism similarity > 20%
        $response = $this->actingAs($user)->post(route('student.research.milestones.submit', $draftMilestone->id), [
            'submission_notes' => 'Full draft thesis chapters 1-5.',
            'plagiarism_similarity_index' => 28.5,
        ]);

        $response->assertSessionHasErrors('plagiarism_similarity_index');

        // 2. Accepts plagiarism similarity <= 20%
        $responseClean = $this->actingAs($user)->post(route('student.research.milestones.submit', $draftMilestone->id), [
            'submission_notes' => 'Clean revised draft chapters 1-5 with revised citations.',
            'plagiarism_similarity_index' => 14.2,
        ]);

        $responseClean->assertSessionHas('success');

        $this->assertDatabaseHas('postgraduate_research_milestones', [
            'id' => $draftMilestone->id,
            'status' => 'submitted',
            'plagiarism_similarity_index' => 14.2,
        ]);
    }

    public function test_candidate_can_access_research_index_page_with_postgraduate_profile(): void
    {
        [$user, $student, $supervisor, $project] = $this->createPgCandidate();

        // Attach a PostgraduateStudentProfile
        \App\Models\PostgraduateStudentProfile::create([
            'student_id' => $student->id,
            'spgs_file_number' => 'SPGS/ENG/2026/001',
            'candidacy_status' => 'confirmed',
            'entry_cgpa' => 4.25,
            'work_experience_years' => 4,
        ]);

        $response = $this->actingAs($user)->get(route('student.research.index'));
        $response->assertOk();
    }
}
