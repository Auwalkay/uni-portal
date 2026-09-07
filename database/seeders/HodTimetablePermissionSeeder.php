<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HodTimetablePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
