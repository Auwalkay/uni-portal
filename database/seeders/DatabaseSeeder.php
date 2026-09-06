<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AcademicSeeder::class,
            NigeriaAcademicSeeder::class,
            SubjectSeeder::class,
            AcademicRecordsSeeder::class,
            FeeSeeder::class,
            AdminUserSeeder::class,
            DesignationSeeder::class,
            NigeriaStateLgaSeeder::class,
            LibrarySeeder::class,
            SickbaySeeder::class,
            InventoryCategorySeeder::class,
        ]);
    }
}
