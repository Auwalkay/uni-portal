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

class AdmissionPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Faculty $faculty;
    protected Department $department;
    protected Programme $programme;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('access_admin_dashboard');
        Permission::findOrCreate('view_applications');

        $role = Role::findOrCreate('Super Admin');
        $role->givePermissionTo([
            'access_admin_dashboard',
            'view_applications',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Officer',
            'email' => 'admin.admissions@portal.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        $this->faculty = Faculty::create([
            'name' => 'Faculty of Science',
            'code' => 'SCI',
        ]);

        $this->department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $this->faculty->id,
        ]);

        $this->programme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'type' => 'UG',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_paginated_applicants_list()
    {
        for ($i = 1; $i <= 25; $i++) {
            $user = User::create([
                'name' => "Applicant User {$i}",
                'email' => "applicant{$i}@test.com",
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            Applicant::create([
                'user_id' => $user->id,
                'jamb_registration_number' => "JAMB2026".str_pad($i, 4, '0', STR_PAD_LEFT),
                'application_mode' => 'UTME',
                'status' => 'pending',
                'program_choice_1' => $this->programme->id,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Admissions/Index')
            ->has('applicants.data', 15) // default per_page is 15
            ->where('applicants.total', 25)
            ->where('applicants.current_page', 1)
            ->where('applicants.last_page', 2)
            ->where('applicants.per_page', 15)
            ->where('applicants.from', 1)
            ->where('applicants.to', 15)
        );
    }

    public function test_per_page_controls_page_size_and_clamped()
    {
        for ($i = 1; $i <= 25; $i++) {
            $user = User::create([
                'name' => "Student Candidate {$i}",
                'email' => "candidate{$i}@test.com",
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            Applicant::create([
                'user_id' => $user->id,
                'jamb_registration_number' => "JAMB2026".str_pad($i, 4, '0', STR_PAD_LEFT),
                'application_mode' => 'UTME',
                'status' => 'submitted',
                'program_choice_1' => $this->programme->id,
            ]);
        }

        // Test per_page = 10
        $response10 = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index', ['per_page' => 10]));

        $response10->assertOk();
        $response10->assertInertia(fn (Assert $page) => $page
            ->has('applicants.data', 10)
            ->where('applicants.per_page', 10)
            ->where('applicants.last_page', 3)
            ->where('filters.per_page', 10)
        );

        // Test per_page = 25
        $response25 = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index', ['per_page' => 25]));

        $response25->assertOk();
        $response25->assertInertia(fn (Assert $page) => $page
            ->has('applicants.data', 25)
            ->where('applicants.per_page', 25)
            ->where('applicants.last_page', 1)
        );

        // Test clamped per_page: below 5 gets clamped to 5
        $responseClampedLow = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index', ['per_page' => 1]));

        $responseClampedLow->assertOk();
        $responseClampedLow->assertInertia(fn (Assert $page) => $page
            ->has('applicants.data', 5)
            ->where('applicants.per_page', 5)
        );
    }

    public function test_filters_and_search_persist_in_pagination_links()
    {
        for ($i = 1; $i <= 20; $i++) {
            $user = User::create([
                'name' => "Pending Applicant {$i}",
                'email' => "pending{$i}@test.com",
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            Applicant::create([
                'user_id' => $user->id,
                'jamb_registration_number' => "PENDING".str_pad($i, 4, '0', STR_PAD_LEFT),
                'application_mode' => 'UTME',
                'status' => 'pending',
                'program_choice_1' => $this->programme->id,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index', [
                'status' => 'pending',
                'search' => 'Applicant',
                'per_page' => 10,
            ]));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->has('applicants.links');
            $links = $page->toArray()['props']['applicants']['links'];
            
            // Check that the next page link (page 2) contains the query parameters
            $page2Link = collect($links)->firstWhere('label', '2');
            $this->assertNotNull($page2Link);
            $this->assertStringContainsString('status=pending', $page2Link['url']);
            $this->assertStringContainsString('search=Applicant', $page2Link['url']);
            $this->assertStringContainsString('per_page=10', $page2Link['url']);
        });
    }

    public function test_search_grouping_does_not_leak_across_statuses()
    {
        $user1 = User::create([
            'name' => 'John Alpha',
            'email' => 'john.alpha@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        Applicant::create([
            'user_id' => $user1->id,
            'jamb_registration_number' => 'JAMBALPHA123',
            'application_mode' => 'UTME',
            'status' => 'rejected', // status is rejected
            'program_choice_1' => $this->programme->id,
        ]);

        $user2 = User::create([
            'name' => 'Jane Beta',
            'email' => 'jane.beta@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        Applicant::create([
            'user_id' => $user2->id,
            'jamb_registration_number' => 'JAMBBETA456',
            'application_mode' => 'UTME',
            'status' => 'admitted', // status is admitted
            'program_choice_1' => $this->programme->id,
        ]);

        // When filtering for admitted applicants and searching for "Alpha",
        // the query MUST NOT return John Alpha who is rejected.
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index', [
                'status' => 'admitted',
                'search' => 'JAMBALPHA123',
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('applicants.data', 0)
            ->where('applicants.total', 0)
        );

        // Searching for Beta with status admitted should return Jane Beta
        $response2 = $this->actingAs($this->adminUser)
            ->get(route('admin.admissions.index', [
                'status' => 'admitted',
                'search' => 'JAMBBETA456',
            ]));

        $response2->assertOk();
        $response2->assertInertia(fn (Assert $page) => $page
            ->has('applicants.data', 1)
            ->where('applicants.total', 1)
            ->where('applicants.data.0.jamb_registration_number', 'JAMBBETA456')
        );
    }
}
