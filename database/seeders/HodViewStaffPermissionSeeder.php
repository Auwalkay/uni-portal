<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HodViewStaffPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
