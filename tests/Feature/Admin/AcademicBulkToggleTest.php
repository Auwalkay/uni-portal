<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Programme;
use App\Models\User;
use App\Services\AcademicCacheService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');

    $this->faculty = Faculty::create([
        'name' => 'Faculty of Engineering',
        'code' => 'ENG',
        'is_active' => true,
    ]);

    $this->department = Department::create([
        'name' => 'Electrical Engineering',
        'code' => 'EEE',
        'faculty_id' => $this->faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    $this->programme = Programme::create([
        'name' => 'B.Eng Electrical Engineering',
        'department_id' => $this->department->id,
        'type' => 'UG',
        'duration' => 5,
        'is_active' => true,
    ]);
});

test('admin can bulk deactivate courses', function () {
    $course1 = Course::create([
        'code' => 'EEE 201',
        'title' => 'Circuit Theory I',
        'units' => 3,
        'level' => 200,
        'semester' => '1',
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);

    $course2 = Course::create([
        'code' => 'EEE 202',
        'title' => 'Circuit Theory II',
        'units' => 3,
        'level' => 200,
        'semester' => '2',
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'course',
            'ids' => [$course1->id, $course2->id],
            'is_active' => false,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($course1->fresh()->is_active)->toBeFalse();
    expect($course2->fresh()->is_active)->toBeFalse();
});

test('admin can bulk activate courses', function () {
    $course1 = Course::create([
        'code' => 'EEE 301',
        'title' => 'Electromagnetism',
        'units' => 3,
        'level' => 300,
        'semester' => '1',
        'department_id' => $this->department->id,
        'is_active' => false,
    ]);

    $course2 = Course::create([
        'code' => 'EEE 302',
        'title' => 'Power Systems',
        'units' => 3,
        'level' => 300,
        'semester' => '2',
        'department_id' => $this->department->id,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'course',
            'ids' => [$course1->id, $course2->id],
            'is_active' => true,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($course1->fresh()->is_active)->toBeTrue();
    expect($course2->fresh()->is_active)->toBeTrue();
});

test('admin can bulk activate and deactivate departments', function () {
    $dept2 = Department::create([
        'name' => 'Mechanical Engineering',
        'code' => 'MEE',
        'faculty_id' => $this->faculty->id,
        'is_academic' => true,
        'is_active' => true,
    ]);

    // Deactivate both
    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'department',
            'ids' => [$this->department->id, $dept2->id],
            'is_active' => false,
        ]);

    $response->assertRedirect();
    expect($this->department->fresh()->is_active)->toBeFalse();
    expect($dept2->fresh()->is_active)->toBeFalse();

    // Activate both back
    $response2 = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'department',
            'ids' => [$this->department->id, $dept2->id],
            'is_active' => true,
        ]);

    $response2->assertRedirect();
    expect($this->department->fresh()->is_active)->toBeTrue();
    expect($dept2->fresh()->is_active)->toBeTrue();
});

test('admin can bulk activate and deactivate programmes', function () {
    $prog2 = Programme::create([
        'name' => 'B.Eng Computer Engineering',
        'department_id' => $this->department->id,
        'type' => 'UG',
        'duration' => 5,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'programme',
            'ids' => [$this->programme->id, $prog2->id],
            'is_active' => false,
        ]);

    $response->assertRedirect();
    expect($this->programme->fresh()->is_active)->toBeFalse();
    expect($prog2->fresh()->is_active)->toBeFalse();
});

test('admin can bulk activate and deactivate faculties', function () {
    $faculty2 = Faculty::create([
        'name' => 'Faculty of Law',
        'code' => 'LAW',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'faculty',
            'ids' => [$this->faculty->id, $faculty2->id],
            'is_active' => false,
        ]);

    $response->assertRedirect();
    expect($this->faculty->fresh()->is_active)->toBeFalse();
    expect($faculty2->fresh()->is_active)->toBeFalse();
});

test('unauthorized user without academic permissions cannot bulk toggle', function () {
    $unauthorizedUser = User::factory()->create();
    $unauthorizedUser->assignRole('student');

    $response = $this->actingAs($unauthorizedUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'course',
            'ids' => [$this->department->id],
            'is_active' => false,
        ]);

    $response->assertForbidden();
});

test('bulk toggle validates input parameters', function () {
    $response = $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'invalid_type',
            'ids' => [],
        ]);

    $response->assertSessionHasErrors(['type', 'ids', 'is_active']);
});

test('bulk toggle flushes academic cache', function () {
    Cache::put('academic_stats_summary', ['test' => 123]);
    Cache::put('all_departments', ['test' => 456]);
    expect(Cache::has('academic_stats_summary'))->toBeTrue();
    expect(Cache::has('all_departments'))->toBeTrue();

    $this->actingAs($this->adminUser)
        ->post(route('admin.academics.bulk_toggle'), [
            'type' => 'department',
            'ids' => [$this->department->id],
            'is_active' => false,
        ]);

    expect(Cache::has('academic_stats_summary'))->toBeFalse();
    expect(Cache::has('all_departments'))->toBeFalse();
});
