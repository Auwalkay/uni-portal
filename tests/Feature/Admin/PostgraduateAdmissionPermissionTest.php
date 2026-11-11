<?php

namespace Tests\Feature\Admin;

use App\Models\Applicant;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgraduateAdmissionPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Faculty $faculty;
    protected Department $department;
    protected Programme $ugProgramme;
    protected Programme $pgProgramme;

    protected User $ugApplicantUser;
    protected Applicant $ugApplicant;

    protected User $pgApplicantUser;
    protected Applicant $pgApplicant;

    protected function setUp(): void
    {
        parent::setUp();

        // Register all needed permissions
        foreach ([
            'access_admin_dashboard',
            'view_applications',
            'review_applications',
            'admit_students',
            'view_pg_applications',
            'review_pg_applications',
            'admit_pg_students',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->faculty = Faculty::create([
            'name' => 'Faculty of Science',
            'code' => 'SCI',
        ]);

        $this->department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $this->faculty->id,
        ]);

        $this->ugProgramme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'type' => 'UG',
            'academic_stream' => 'UG',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->pgProgramme = Programme::create([
            'name' => 'M.Sc Computer Science',
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        // Create UG Applicant
        $this->ugApplicantUser = User::create([
            'name' => 'UG Applicant John',
            'email' => 'ug.john@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->ugApplicant = Applicant::create([
            'user_id' => $this->ugApplicantUser->id,
            'jamb_registration_number' => 'JAMB2026001',
            'application_number' => 'APP-UG-001',
            'application_mode' => 'UTME',
            'status' => 'submitted',
            'program_choice_1' => $this->ugProgramme->id,
        ]);

        // Create PG Applicant
        $this->pgApplicantUser = User::create([
            'name' => 'PG Applicant Jane',
            'email' => 'pg.jane@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->pgApplicant = Applicant::create([
            'user_id' => $this->pgApplicantUser->id,
            'application_number' => 'SPGS2026001',
            'jamb_registration_number' => 'PG2026001',
            'application_mode' => 'PG',
            'status' => 'submitted',
            'program_choice_1' => $this->pgProgramme->id,
            'previous_qualification' => 'B.Sc',
            'previous_institution' => 'University of Lagos',
            'previous_class_of_degree' => 'First Class',
            'previous_cgpa' => 4.65,
        ]);
    }

    private function createStaffWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'Role_' . uniqid(), 'guard_name' => 'web']);
        $role->givePermissionTo(array_merge(['access_admin_dashboard'], $permissions));

        $user = User::create([
            'name' => 'Staff ' . uniqid(),
            'email' => 'staff_' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_user_with_only_view_applications_sees_only_ug_applicants()
    {
        $ugOfficer = $this->createStaffWithPermissions(['view_applications']);

        $response = $this->actingAs($ugOfficer)->get(route('admin.admissions.ug.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Admissions/Index')
            ->has('applicants.data', 1)
            ->where('applicants.data.0.id', $this->ugApplicant->id)
        );

        // Accessing PG module should be forbidden
        $pgResponse = $this->actingAs($ugOfficer)->get(route('admin.admissions.pg.index'));
        $pgResponse->assertForbidden();
    }

    public function test_user_with_only_view_pg_applications_sees_only_pg_applicants()
    {
        $pgOfficer = $this->createStaffWithPermissions(['view_pg_applications']);

        $response = $this->actingAs($pgOfficer)->get(route('admin.admissions.pg.index'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Postgraduate/Admissions/Index')
            ->has('applicants.data', 1)
            ->where('applicants.data.0.id', $this->pgApplicant->id)
        );

        // Accessing UG module should be forbidden
        $ugResponse = $this->actingAs($pgOfficer)->get(route('admin.admissions.ug.index'));
        $ugResponse->assertForbidden();
    }

    public function test_hub_redirects_based_on_user_permissions()
    {
        $ugOfficer = $this->createStaffWithPermissions(['view_applications']);
        $responseUG = $this->actingAs($ugOfficer)->get(route('admin.admissions.index'));
        $responseUG->assertRedirect(route('admin.admissions.ug.index'));

        $pgOfficer = $this->createStaffWithPermissions(['view_pg_applications']);
        $responsePG = $this->actingAs($pgOfficer)->get(route('admin.admissions.index'));
        $responsePG->assertRedirect(route('admin.admissions.pg.index'));
    }

    public function test_cross_stream_show_page_guards()
    {
        $ugOfficer = $this->createStaffWithPermissions(['view_applications']);
        $pgOfficer = $this->createStaffWithPermissions(['view_pg_applications']);

        // UG officer can view UG applicant, but cannot view PG applicant
        $this->actingAs($ugOfficer)->get(route('admin.admissions.ug.show', $this->ugApplicant->id))->assertOk();
        $this->actingAs($ugOfficer)->get(route('admin.admissions.pg.show', $this->pgApplicant->id))->assertForbidden();

        // PG officer can view PG applicant, but cannot view UG applicant
        $this->actingAs($pgOfficer)->get(route('admin.admissions.pg.show', $this->pgApplicant->id))->assertOk();
        $this->actingAs($pgOfficer)->get(route('admin.admissions.ug.show', $this->ugApplicant->id))->assertForbidden();
    }

    public function test_screening_and_update_permissions_enforced()
    {
        $ugReviewer = $this->createStaffWithPermissions(['view_applications', 'review_applications']);
        $pgReviewer = $this->createStaffWithPermissions(['view_pg_applications', 'review_pg_applications']);

        // UG reviewer can update UG applicant status to screening
        $this->actingAs($ugReviewer)->put(route('admin.admissions.ug.update', $this->ugApplicant->id), [
            'status' => 'screening',
        ])->assertRedirect();
        $this->assertEquals('screening', $this->ugApplicant->fresh()->status);

        // UG reviewer cannot update PG applicant status
        $this->actingAs($ugReviewer)->put(route('admin.admissions.pg.update', $this->pgApplicant->id), [
            'status' => 'screening',
        ])->assertForbidden();

        // PG reviewer can update PG applicant status to screening
        $this->actingAs($pgReviewer)->put(route('admin.admissions.pg.update', $this->pgApplicant->id), [
            'status' => 'screening',
        ])->assertRedirect();
        $this->assertEquals('screening', $this->pgApplicant->fresh()->status);

        // PG reviewer cannot update UG applicant status
        $this->actingAs($pgReviewer)->put(route('admin.admissions.ug.update', $this->ugApplicant->id), [
            'status' => 'screening',
        ])->assertForbidden();
    }

    public function test_admit_student_permission_enforced_between_ug_and_pg()
    {
        \App\Models\Invoice::create([
            'user_id' => $this->pgApplicant->user_id,
            'type' => 'application_fee',
            'status' => 'paid',
            'amount' => 50000,
            'reference' => 'APP-TEST-'.uniqid(),
        ]);

        $pgReviewerOnly = $this->createStaffWithPermissions(['view_pg_applications', 'review_pg_applications']);
        $pgAdmitter = $this->createStaffWithPermissions(['view_pg_applications', 'review_pg_applications', 'admit_pg_students']);

        // Reviewer without admit_pg_students fails with 403
        $this->actingAs($pgReviewerOnly)->put(route('admin.admissions.pg.update', $this->pgApplicant->id), [
            'status' => 'admitted',
            'admitted_level' => '800',
            'admitted_programme_id' => $this->pgProgramme->id,
        ])->assertForbidden();

        // Admitter with admit_pg_students succeeds
        $this->actingAs($pgAdmitter)->put(route('admin.admissions.pg.update', $this->pgApplicant->id), [
            'status' => 'admitted',
            'admitted_level' => '800',
            'admitted_programme_id' => $this->pgProgramme->id,
        ])->assertRedirect();

        $freshPg = $this->pgApplicant->fresh();
        $this->assertEquals('admitted', $freshPg->status);
        $this->assertEquals('800', $freshPg->admitted_level);
        $this->assertEquals($this->pgProgramme->id, $freshPg->admitted_programme_id);
    }

    public function test_enrolled_applicant_cannot_be_updated()
    {
        $pgAdmitter = $this->createStaffWithPermissions(['view_pg_applications', 'review_pg_applications', 'admit_pg_students']);
        $ugAdmitter = $this->createStaffWithPermissions(['view_applications', 'review_applications', 'admit_students']);

        $this->pgApplicant->update(['status' => 'enrolled']);
        $this->ugApplicant->update(['status' => 'enrolled']);

        // Attempting to update enrolled PG applicant fails
        $response = $this->actingAs($pgAdmitter)->put(route('admin.admissions.pg.update', $this->pgApplicant->id), [
            'status' => 'screening',
        ]);
        $response->assertSessionHas('error', 'Enrolled applicant records cannot be modified.');
        $this->assertEquals('enrolled', $this->pgApplicant->fresh()->status);

        // Attempting to update enrolled UG applicant fails
        $responseUg = $this->actingAs($ugAdmitter)->put(route('admin.admissions.ug.update', $this->ugApplicant->id), [
            'status' => 'screening',
        ]);
        $responseUg->assertSessionHas('error', 'Enrolled applicant records cannot be modified.');
        $this->assertEquals('enrolled', $this->ugApplicant->fresh()->status);
    }

    public function test_download_admission_letter_handles_application_number_with_slashes()
    {
        $this->pgApplicant->update([
            'status' => 'admitted',
            'application_number' => 'APP/2026/6709',
        ]);

        $pgViewer = $this->createStaffWithPermissions(['view_pg_applications']);

        $response = $this->actingAs($pgViewer)->get(route('admin.admissions.pg.letter', $this->pgApplicant->id));

        $response->assertOk();
        $this->assertStringContainsString('SPGS_Admission_Letter_APP_2026_6709.pdf', $response->headers->get('content-disposition'));
    }
}

