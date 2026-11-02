<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\PostgraduateCandidacyRequest;
use App\Models\PostgraduateProgressReport;
use App\Models\PostgraduateResearchProject;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\Postgraduate\PostgraduateCandidacyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PostgraduateCandidacyAndSundryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureStudentProfileCompleted::class);
    }

    protected function createCandidateAndAdmin(): array
    {
        $faculty = Faculty::create(['name' => 'Faculty of Law', 'code' => 'LAW']);
        $dept = Department::create(['name' => 'Jurisprudence & Public Law', 'code' => 'JPL', 'faculty_id' => $faculty->id]);
        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2026-10-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $prog = Programme::create([
            'name' => 'Master of Laws (LL.M)',
            'department_id' => $dept->id,
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'duration' => 2,
        ]);

        $studentUser = User::factory()->create(['name' => 'Fatima Aliyu', 'email' => 'fatima@pg.test']);
        Permission::firstOrCreate(['name' => 'access_student_portal', 'guard_name' => 'web']);
        $studentUser->givePermissionTo('access_student_portal');
        $student = Student::create([
            'user_id' => $studentUser->id,
            'matriculation_number' => 'PG/2026/LLM/012',
            'spgs_file_number' => 'SPGS/LAW/2026/012',
            'program_id' => $prog->id,
            'current_level' => 800,
            'academic_stream' => 'PG',
        ]);

        $project = PostgraduateResearchProject::create([
            'student_id' => $student->id,
            'session_id' => $session->id,
            'pathway_type' => 'academic_dissertation',
            'title' => 'Comparative Analysis of Cyber Sovereignty and Digital Privacy Rights',
            'research_area' => 'Information Technology Law',
            'status' => 'in_progress',
        ]);

        $adminUser = User::factory()->create(['name' => 'SPGS Secretary', 'email' => 'spgs-admin@univ.test']);

        return [$studentUser, $student, $project, $adminUser];
    }

    public function test_student_can_submit_annual_progress_report_and_receive_supervisor_rating(): void
    {
        [$studentUser, $student, $project, $adminUser] = $this->createCandidateAndAdmin();

        $response = $this->actingAs($studentUser)->post(route('student.research.progress-reports.store'), [
            'reporting_period' => 'annual',
            'summary_of_progress' => 'Completed literature review and comparative legal jurisprudence analysis.',
            'chapters_completed' => 'Chapters 1, 2, and 3 completed.',
            'fieldwork_or_lab_status' => 'Court records and digital privacy precedents analyzed across 4 jurisdictions.',
            'publications_or_conferences' => 'Presented at Annual Nigerian Bar Conference 2026.',
            'completion_percentage' => 70,
        ]);

        $response->assertSessionHas('success');

        $report = PostgraduateProgressReport::where('student_id', $student->id)->firstOrFail();
        $this->assertEquals('submitted', $report->spgs_status);
        $this->assertEquals(70, $report->completion_percentage);

        // Supervisor evaluates the report
        $supUser = User::factory()->create();
        $supervisor = Staff::create(['user_id' => $supUser->id, 'staff_number' => 'STF/LAW/01', 'is_academic' => true]);

        $candidacyService = app(PostgraduateCandidacyService::class);
        $reviewed = $candidacyService->reviewProgressReportBySupervisor($report, $supervisor, [
            'supervisor_rating' => 'excellent',
            'supervisor_remarks' => 'Candidate is progressing ahead of schedule with exceptional analytical rigor.',
        ]);

        $this->assertEquals('supervisor_approved', $reviewed->spgs_status);
        $this->assertEquals('excellent', $reviewed->supervisor_rating);
    }

    public function test_student_can_petition_for_title_change_and_spgs_approval_updates_project(): void
    {
        [$studentUser, $student, $project, $adminUser] = $this->createCandidateAndAdmin();

        $response = $this->actingAs($studentUser)->post(route('student.research.candidacy-requests.store'), [
            'request_type' => 'change_of_title',
            'proposed_title' => 'Digital Sovereignty, Cross-Border Cloud Jurisprudence, and Data Governance in West Africa',
            'justification_and_timeline' => 'Title refined based on departmental seminar panel recommendations.',
        ]);

        $response->assertSessionHas('success');

        $petition = PostgraduateCandidacyRequest::where('student_id', $student->id)->firstOrFail();
        $this->assertEquals('pending', $petition->status);

        // SPGS Board reviews & approves the title change petition
        $candidacyService = app(PostgraduateCandidacyService::class);
        $candidacyService->processCandidacyRequest($petition, $adminUser, 'approve');

        $this->assertEquals('spgs_approved', $petition->fresh()->status);
        $this->assertEquals(
            'Digital Sovereignty, Cross-Border Cloud Jurisprudence, and Data Governance in West Africa',
            $project->fresh()->title
        );
    }

    public function test_student_can_log_peer_reviewed_publication_for_spgs_clearance(): void
    {
        [$studentUser, $student, $project, $adminUser] = $this->createCandidateAndAdmin();

        $response = $this->actingAs($studentUser)->post(route('student.research.publications.store'), [
            'article_title' => 'Data Protection in the Age of Algorithmic Processing: An ECOWAS Perspective',
            'journal_name' => 'African Journal of International and Comparative Law',
            'issn_isbn' => '0954-8890',
            'doi_or_url' => 'https://doi.org/10.3366/ajicl.2026.0411',
            'indexing_agency' => 'Scopus',
            'publication_status' => 'published',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('postgraduate_publications', [
            'student_id' => $student->id,
            'project_id' => $project->id,
            'article_title' => 'Data Protection in the Age of Algorithmic Processing: An ECOWAS Perspective',
            'is_spgs_verified' => false,
        ]);
    }
}
