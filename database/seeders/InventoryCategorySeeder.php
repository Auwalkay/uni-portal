<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use Illuminate\Database\Seeder;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Laptops, projectors, cables, and other electronic devices.',
            ],
            [
                'name' => 'Furniture',
                'description' => 'Chairs, tables, desks, and other furniture items.',
            ],
            [
                'name' => 'Stationery',
                'description' => 'Pens, paper, notebooks, and office supplies.',
            ],
            [
                'name' => 'Lab Equipment',
                'description' => 'Microscopes, chemicals, and other laboratory tools.',
            ],
        ];

        foreach ($categories as $category) {
            InventoryCategory::create($category);
        }
    }
}
