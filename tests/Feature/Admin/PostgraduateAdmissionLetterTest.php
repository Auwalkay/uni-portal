<?php

namespace Tests\Feature\Admin;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\Session;
use App\Models\User;
use App\Services\AdmissionLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgraduateAdmissionLetterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'applicant']);
        Permission::firstOrCreate(['name' => 'access_student_portal']);
    }

    public function test_admission_letter_service_resolves_pg_tiers_and_degree_labels(): void
    {
        $service = app(AdmissionLetterService::class);

        $pgd = new Programme(['name' => 'PGD Accounting', 'type' => 'PGD', 'academic_stream' => 'PG']);
        $msc = new Programme(['name' => 'M.Sc. Computer Science', 'type' => 'MSC', 'academic_stream' => 'PG']);
        $mipsa = new Programme(['name' => 'MIPSA - Master in Public Sector Accounting', 'type' => 'MSC', 'academic_stream' => 'PG']);
        $mba = new Programme(['name' => 'Master of Business Administration (MBA)', 'type' => 'MBA', 'academic_stream' => 'PG']);
        $phd = new Programme(['name' => 'Ph.D. Accounting', 'type' => 'PHD', 'academic_stream' => 'PG']);

        $this->assertSame('pgd', $service->resolvePgTier($pgd));
        $this->assertSame('Postgraduate Diploma (PGD)', $service->resolveDegreeLabel($pgd, 'pgd'));

        $this->assertSame('masters', $service->resolvePgTier($msc));
        $this->assertSame('Master of Science (M.Sc.)', $service->resolveDegreeLabel($msc, 'masters'));

        $this->assertSame('masters', $service->resolvePgTier($mipsa));
        $this->assertSame('Master in Public Sector Accounting (MIPSA)', $service->resolveDegreeLabel($mipsa, 'masters'));

        $this->assertSame('masters', $service->resolvePgTier($mba));
        $this->assertSame('Master of Business Administration (MBA)', $service->resolveDegreeLabel($mba, 'masters'));

        $this->assertSame('phd', $service->resolvePgTier($phd));
        $this->assertSame('Doctor of Philosophy (Ph.D.)', $service->resolveDegreeLabel($phd, 'phd'));
    }

    public function test_pg_admission_letter_view_renders_distinct_content_for_pgd_masters_and_phd(): void
    {
        $faculty = Faculty::create(['name' => 'Management Sciences', 'code' => 'MGT']);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Accounting', 'code' => 'ACC']);

        $pgdProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'PGD Accounting',
            'type' => 'PGD',
            'academic_stream' => 'PG',
            'duration' => 1.0,
            'is_active' => true,
        ]);

        $mscProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'M.Sc. Accounting',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'duration' => 1.5,
            'is_active' => true,
        ]);

        $phdProg = Programme::create([
            'department_id' => $dept->id,
            'name' => 'Ph.D. Accounting',
            'type' => 'PHD',
            'academic_stream' => 'PG',
            'duration' => 3.0,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['name' => 'Amina Bello']);
        $applicant = Applicant::create([
            'user_id' => $user->id,
            'application_number' => 'MIU/SPGS/ADM/2026/001',
            'application_mode' => 'PG',
            'program_choice_1' => $pgdProg->id,
            'admitted_programme_id' => $pgdProg->id,
            'status' => 'admitted',
        ]);

        $pgdHtml = view('documents.pg_admission_letter', [
            'applicant' => $applicant,
            'programme_name' => $pgdProg->name,
            'session_name' => '2026/2027',
            'pg_tier' => 'pgd',
            'degree_label' => 'Postgraduate Diploma (PGD)',
        ])->render();

        $this->assertStringContainsString('SCHOOL OF POSTGRADUATE STUDIES', strtoupper($pgdHtml));
        $this->assertStringContainsString('Two (2) Semesters (One Academic Session).', $pgdHtml);
        $this->assertStringContainsString('Higher National Diploma (HND)', $pgdHtml);
        $this->assertStringContainsString('Scan to Verify Admission', $pgdHtml);
        $this->assertStringNotContainsString('Financial Information', $pgdHtml);

        $mastersHtml = view('documents.pg_admission_letter', [
            'applicant' => $applicant,
            'programme_name' => $mscProg->name,
            'session_name' => '2026/2027',
            'pg_tier' => 'masters',
            'degree_label' => 'Master of Science (M.Sc.)',
        ])->render();

        $this->assertStringContainsString('Minimum of Three (3) Semesters (18 Months).', $mastersHtml);
        $this->assertStringContainsString('minimum CGPA of 3.00', $mastersHtml);
        $this->assertStringNotContainsString('Financial Information', $mastersHtml);

        $phdHtml = view('documents.pg_admission_letter', [
            'applicant' => $applicant,
            'programme_name' => $phdProg->name,
            'session_name' => '2026/2027',
            'pg_tier' => 'phd',
            'degree_label' => 'Doctor of Philosophy (Ph.D.)',
        ])->render();

        $this->assertStringContainsString('Minimum of Six (6) Semesters (Three Academic Years).', $phdHtml);
        $this->assertStringContainsString('minimum CGPA of 3.5', $phdHtml);
        $this->assertStringContainsString('research synopsis and assignment of supervisor(s)', $phdHtml);
        $this->assertStringNotContainsString('Financial Information', $phdHtml);
    }

    public function test_admin_and_applicant_routes_download_pg_admission_letters_for_each_tier(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Session::create([
            'name' => '2026/2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI']);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science', 'code' => 'CSC']);

        foreach ([
            ['name' => 'PGD Computer Science', 'type' => 'PGD', 'duration' => 1.0],
            ['name' => 'M.Sc. Computer Science', 'type' => 'MSC', 'duration' => 1.5],
            ['name' => 'Ph.D. Computer Science', 'type' => 'PHD', 'duration' => 3.0],
        ] as $idx => $progData) {
            $prog = Programme::create([
                'department_id' => $dept->id,
                'name' => $progData['name'],
                'type' => $progData['type'],
                'academic_stream' => 'PG',
                'duration' => $progData['duration'],
                'is_active' => true,
            ]);

            $applicantUser = User::factory()->create();
            $applicantUser->assignRole('applicant');
            $applicantUser->givePermissionTo('access_student_portal');

            $applicant = Applicant::create([
                'user_id' => $applicantUser->id,
                'application_number' => "SPGS/2026/00{$idx}",
                'application_mode' => 'PG',
                'program_choice_1' => $prog->id,
                'admitted_programme_id' => $prog->id,
                'status' => 'admitted',
            ]);

            $adminResp = $this->actingAs($admin)->get(route('admin.admissions.pg.letter', $applicant));
            $adminResp->assertStatus(200);
            $this->assertSame('application/pdf', $adminResp->headers->get('content-type'));

            $studentResp = $this->actingAs($applicantUser)->get(route('student.admission_letter.download'));
            $studentResp->assertStatus(200);
            $this->assertSame('application/pdf', $studentResp->headers->get('content-type'));
        }
    }
}
