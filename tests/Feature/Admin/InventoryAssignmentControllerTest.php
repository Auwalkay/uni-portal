<?php

namespace Tests\Feature\Admin;

use App\Models\Building;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected InventoryItem $item;
    protected Building $building;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');

        $category = \App\Models\InventoryCategory::create([
            'name' => 'Furniture',
            'description' => 'Office furniture',
        ]);

        $this->item = InventoryItem::create([
            'inventory_category_id' => $category->id,
            'name' => 'Office Desk',
            'sku' => 'DSK-001',
            'total_quantity' => 10,
            'available_quantity' => 8,
            'unit_of_measure' => 'pieces',
            'condition' => 'good',
        ]);

        $this->building = Building::create([
            'name' => 'Faculty of Engineering Building',
            'code' => 'ENG-BLD',
            'building_type' => 'faculty_block',
        ]);

        $faculty = Faculty::create([
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id,
            'name' => 'Civil Engineering',
            'code' => 'CVE',
        ]);

        $staffUser = User::factory()->create(['name' => 'Professor Smith']);
        $this->staff = Staff::create([
            'user_id' => $staffUser->id,
            'department_id' => $department->id,
            'staff_number' => 'STF-001',
        ]);
    }

    public function test_index_can_eager_load_assignments_for_both_buildings_and_staff()
    {
        // 1. Assignment to Building
        $buildingAssignment = InventoryAssignment::create([
            'inventory_item_id' => $this->item->id,
            'building_id' => $this->building->id,
            'assignable_type' => Building::class,
            'assignable_id' => $this->building->id,
            'quantity' => 1,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        // 2. Assignment to Staff
        $staffAssignment = InventoryAssignment::create([
            'inventory_item_id' => $this->item->id,
            'staff_id' => $this->staff->id,
            'assignable_type' => Staff::class,
            'assignable_id' => $this->staff->id,
            'quantity' => 1,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.inventory.assignments.index'));

        $response->assertOk();

        // Verify returning an item assigned to a building does not trigger undefined user relation
        $returnResponse = $this->actingAs($this->admin)->put(route('admin.inventory.assignments.return', $buildingAssignment->id), [
            'status' => 'returned',
            'condition_on_return' => 'good',
        ]);
        $returnResponse->assertRedirect();
        $this->assertEquals('returned', $buildingAssignment->fresh()->status);

        // Verify export collection handles building assignable without error
        $export = new \App\Exports\InventoryAssignmentExport();
        $collection = $export->collection();
        $this->assertCount(2, $collection);
        $this->assertEquals('Campus Building', $collection->first()['target_type']);
        $this->assertStringContainsString('Faculty of Engineering Building', $collection->first()['destination']);
    }
}
