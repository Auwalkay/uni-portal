<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
