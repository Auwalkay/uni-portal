<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (tenant()) {
            $this->call([
                RolesAndPermissionsSeeder::class,
                    // AcademicSeeder::class,
                    // NigeriaAcademicSeeder::class,
                SubjectSeeder::class,
                    // AcademicRecordsSeeder::class,
                FeeSeeder::class,
                NigeriaStateLgaSeeder::class,
                TenantAdminSeeder::class,
            ]);
        } else {
            $this->call([
                CentralUserSeeder::class,
                CentralRolesSeeder::class,
            ]);
        }
    }
}
