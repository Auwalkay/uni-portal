<?php

use App\Models\Building;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');
});

test('admin can create building with no code and it auto-generates uppercase slug', function () {
    $response = $this->actingAs($this->adminUser)->post('/admin/buildings', [
        'name' => 'Senate Administrative Complex',
        'code' => '',
        'building_type' => 'admin_block',
        'capacity' => null,
        'usable_for_exams' => false,
        'status' => 'active',
        'description' => 'Central university administrative building.',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $building = Building::where('name', 'Senate Administrative Complex')->first();
    expect($building)->not->toBeNull();
    expect($building->code)->toBe('SENATE-ADMINISTRATIVE-COMPLEX');
    expect($building->capacity)->toBeNull();
    expect($building->usable_for_exams)->toBeFalse();
    expect($building->building_type)->toBe('admin_block');
});

test('creating two buildings with same name and blank code generates unique incremented slug', function () {
    $this->actingAs($this->adminUser)->post('/admin/buildings', [
        'name' => 'Faculty Office Annex',
        'code' => '',
        'building_type' => 'office',
        'capacity' => null,
        'usable_for_exams' => false,
        'status' => 'active',
    ]);

    $first = Building::where('name', 'Faculty Office Annex')->first();
    expect($first->code)->toBe('FACULTY-OFFICE-ANNEX');

    // Create second with identical name and no code
    $this->actingAs($this->adminUser)->post('/admin/buildings', [
        'name' => 'Faculty Office Annex',
        'code' => '',
        'building_type' => 'office',
        'capacity' => null,
        'usable_for_exams' => false,
        'status' => 'active',
    ]);

    $buildings = Building::where('name', 'Faculty Office Annex')->get();
    expect($buildings)->toHaveCount(2);
    expect($buildings->pluck('code')->all())->toContain('FACULTY-OFFICE-ANNEX', 'FACULTY-OFFICE-ANNEX-2');
});

test('admin can create lecture hall with seating capacity and explicit code', function () {
    $response = $this->actingAs($this->adminUser)->post('/admin/buildings', [
        'name' => 'Main Engineering Auditorium',
        'code' => 'eng-aud-1',
        'building_type' => 'auditorium',
        'capacity' => 350,
        'usable_for_exams' => true,
        'status' => 'active',
    ]);

    $response->assertSessionHasNoErrors();

    $building = Building::where('code', 'ENG-AUD-1')->first();
    expect($building)->not->toBeNull();
    expect($building->capacity)->toBe(350);
    expect($building->usable_for_exams)->toBeTrue();
});

test('admin can update building and edit its details', function () {
    $building = Building::create([
        'name' => 'Old Security Post',
        'code' => 'OLD-SEC',
        'building_type' => 'other',
        'capacity' => null,
        'usable_for_exams' => false,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->adminUser)->put("/admin/buildings/{$building->id}", [
        'name' => 'Main Gate Security Complex',
        'code' => '', // blank should regenerate slug
        'building_type' => 'maintenance',
        'capacity' => null,
        'usable_for_exams' => false,
        'status' => 'active',
    ]);

    $response->assertSessionHasNoErrors();

    $building->refresh();
    expect($building->name)->toBe('Main Gate Security Complex');
    expect($building->code)->toBe('MAIN-GATE-SECURITY-COMPLEX');
    expect($building->capacity)->toBeNull();
});

test('admin can view buildings index with expanded facility types and nullable seating stats', function () {
    Building::create([
        'name' => 'Lecture Room 1',
        'code' => 'LR-1',
        'building_type' => 'classroom',
        'capacity' => 50,
        'usable_for_exams' => true,
        'status' => 'active',
    ]);

    Building::create([
        'name' => 'Bursary Admin Block',
        'code' => 'BURSARY-BLOCK',
        'building_type' => 'admin_block',
        'capacity' => null,
        'usable_for_exams' => false,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->adminUser)->get('/admin/buildings');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Buildings/Index')
        ->has('buildings.data', 2)
        ->where('stats.total_buildings', 2)
        ->where('stats.total_capacity', 50)
        ->where('stats.exam_usable_count', 1)
    );
});

