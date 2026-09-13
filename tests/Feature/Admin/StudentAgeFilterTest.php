<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage_users']);
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    
    $this->admin = User::factory()->create();
    $this->admin->assignRole($adminRole);
    $this->admin->givePermissionTo('manage_users');

    $faculty = Faculty::create(['name' => 'Science']);
    $department = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
    $programme = Programme::create(['name' => 'B.Sc Computer Science', 'code' => 'CS-BSC', 'department_id' => $department->id, 'duration' => 4]);
    $session = Session::create(['name' => '2025/2026', 'start_date' => now(), 'end_date' => now()->addYear(), 'is_current' => true]);

    // Student A: 20 years old (dob = 20 years ago)
    $userA = User::factory()->create(['name' => 'Student Twenty']);
    $this->studentA = Student::create([
        'user_id' => $userA->id,
        'matriculation_number' => 'MAT200',
        'faculty_id' => $faculty->id,
        'department_id' => $department->id,
        'program_id' => $programme->id,
        'admitted_session_id' => $session->id,
        'current_level' => '200',
        'gender' => 'male',
        'dob' => now()->subYears(20)->format('Y-m-d'),
    ]);

    // Student B: 30 years old (dob = 30 years ago)
    $userB = User::factory()->create(['name' => 'Student Thirty']);
    $this->studentB = Student::create([
        'user_id' => $userB->id,
        'matriculation_number' => 'MAT300',
        'faculty_id' => $faculty->id,
        'department_id' => $department->id,
        'program_id' => $programme->id,
        'admitted_session_id' => $session->id,
        'current_level' => '300',
        'gender' => 'female',
        'dob' => now()->subYears(30)->format('Y-m-d'),
    ]);
});

test('admin can filter students by minimum age', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['min_age' => 25]));

    $response->assertStatus(200);
    $page = $response->inertia()->toArray()['props'];
    
    $studentIds = collect($page['students']['data'])->pluck('id');
    expect($studentIds)->toContain($this->studentB->id);
    expect($studentIds)->not->toContain($this->studentA->id);
});

test('admin can filter students by maximum age', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['max_age' => 22]));

    $response->assertStatus(200);
    $page = $response->inertia()->toArray()['props'];
    
    $studentIds = collect($page['students']['data'])->pluck('id');
    expect($studentIds)->toContain($this->studentA->id);
    expect($studentIds)->not->toContain($this->studentB->id);
});

test('admin can filter students by exact age range', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.students.index', ['min_age' => 19, 'max_age' => 21]));

    $response->assertStatus(200);
    $page = $response->inertia()->toArray()['props'];
    
    $studentIds = collect($page['students']['data'])->pluck('id');
    expect($studentIds)->toContain($this->studentA->id);
    expect($studentIds)->not->toContain($this->studentB->id);
});
