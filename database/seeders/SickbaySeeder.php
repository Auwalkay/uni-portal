<?php

namespace Database\Seeders;

use App\Models\SickbayItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SickbaySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Supplies Inventory
        $supplies = [
            ['name' => 'Adhesive Bandages (Box of 100)', 'category' => 'First Aid Supply', 'stock_quantity' => 15, 'alert_threshold' => 5],
            ['name' => 'Paracetamol 500mg (100 tablets)', 'category' => 'OTC Drug', 'stock_quantity' => 50, 'alert_threshold' => 10],
            ['name' => 'Ibuprofen 400mg (100 tablets)', 'category' => 'OTC Drug', 'stock_quantity' => 20, 'alert_threshold' => 5],
            ['name' => 'Iodine Antiseptic Liquid (100ml)', 'category' => 'First Aid Supply', 'stock_quantity' => 12, 'alert_threshold' => 3],
            ['name' => 'Salbutamol Inhaler 100mcg', 'category' => 'OTC Drug', 'stock_quantity' => 8, 'alert_threshold' => 2],
            ['name' => 'Antacid Tablets (Roll of 12)', 'category' => 'OTC Drug', 'stock_quantity' => 45, 'alert_threshold' => 8],
            ['name' => 'Sterile Gauze Pads (Pack of 50)', 'category' => 'First Aid Supply', 'stock_quantity' => 10, 'alert_threshold' => 4],
        ];

        foreach ($supplies as $supply) {
            SickbayItem::firstOrCreate(['name' => $supply['name']], $supply);
        }

        // 2. Seed Beds
        $beds = ['Bed 1', 'Bed 2', 'Bed 3', 'Bed 4'];
        foreach ($beds as $bedName) {
            \App\Models\SickbayBed::firstOrCreate(['name' => $bedName]);
        }

        // 3. Seed default Sickbay Nurse User
        $nurseUser = User::firstOrCreate(
            ['email' => 'nurse@portal.com'],
            [
                'name' => 'Nurse Sarah Jenkins',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $nurseUser->syncRoles(['sickbay_nurse']);
    }
}
