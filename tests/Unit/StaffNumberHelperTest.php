<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\StaffNumberHelper;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffNumberHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_sequential_staff_numbers_in_ascending_order()
    {
        $user1 = User::factory()->create();
        $num1 = StaffNumberHelper::generate(2026);
        $this->assertEquals('MIUERP20260001', $num1);

        Staff::create([
            'user_id' => $user1->id,
            'staff_number' => $num1,
        ]);

        $user2 = User::factory()->create();
        $num2 = StaffNumberHelper::generate(2026);
        $this->assertEquals('MIUERP20260002', $num2);

        Staff::create([
            'user_id' => $user2->id,
            'staff_number' => $num2,
        ]);

        $num3 = StaffNumberHelper::generate(2026);
        $this->assertEquals('MIUERP20260003', $num3);
    }

    public function test_skips_existing_numbers_and_continues_in_ascending_order()
    {
        $user1 = User::factory()->create();
        // Manually create 0001 and 0002
        Staff::create([
            'user_id' => $user1->id,
            'staff_number' => 'MIUERP20260001',
        ]);

        $user2 = User::factory()->create();
        Staff::create([
            'user_id' => $user2->id,
            'staff_number' => 'MIUERP20260002',
        ]);

        // Also pre-exist 0003 (e.g. legacy/imported)
        $user3 = User::factory()->create();
        Staff::create([
            'user_id' => $user3->id,
            'staff_number' => 'MIUERP20260003',
        ]);

        // Next should be 0004
        $num = StaffNumberHelper::generate(2026);
        $this->assertEquals('MIUERP20260004', $num);
    }

    public function test_fills_gaps_in_ascending_order()
    {
        $user1 = User::factory()->create();
        Staff::create([
            'user_id' => $user1->id,
            'staff_number' => 'MIUERP20260001',
        ]);

        // 0002 is missing, but 0003 exists
        $user3 = User::factory()->create();
        Staff::create([
            'user_id' => $user3->id,
            'staff_number' => 'MIUERP20260003',
        ]);

        // Next generated should be 0002
        $num = StaffNumberHelper::generate(2026);
        $this->assertEquals('MIUERP20260002', $num);
    }
}
