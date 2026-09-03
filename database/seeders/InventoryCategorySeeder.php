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
                'name' => 'Office & Stationeries',
                'description' => 'A4 Paper reams, writing pads, pens, whiteboard markers, files, folders, staplers, and general office paper products.',
            ],
            [
                'name' => 'Computers & IT Hardware',
                'description' => 'Laptops, desktop computers, monitors, keyboards, mice, printers, UPS power units, routers, switches, and IT accessories.',
            ],
            [
                'name' => 'Cleaning & Janitorial Supplies',
                'description' => 'Liquid soaps, detergents, disinfectants, mops, brooms, trash bins, hand sanitizers, paper towels, and sanitation equipment.',
            ],
            [
                'name' => 'Building & Maintenance Materials',
                'description' => 'Cement bags, paint drums, plumbing pipes, electrical fittings, LED bulbs, wiring cables, switches, and masonry tools.',
            ],
            [
                'name' => 'Laboratory Reagents & Glassware',
                'description' => 'Science lab chemicals, test tubes, glass beakers, microscopes, chemical reagents, Bunsen burners, and safety goggles.',
            ],
            [
                'name' => 'Agricultural & Farm Inputs',
                'description' => 'Fertilizers, crop seeds, pesticides, hoes, cutlasses, wheelbarrows, irrigation hoses, and farm machinery spares.',
            ],
            [
                'name' => 'Medical & Sickbay Supplies',
                'description' => 'First aid kits, bandages, disposable syringes, surgical gloves, thermometers, stethoscopes, and essential clinic medications.',
            ],
            [
                'name' => 'Furniture & Fixtures',
                'description' => 'Executive office desks, ergonomic chairs, lecture hall wooden benches, filing cabinets, and library bookshelves.',
            ],
            [
                'name' => 'Electrical & Power Backup',
                'description' => 'Solar panels, inverter units, deep-cycle batteries, generator replacement filters, automatic changeover switches, and heavy cables.',
            ],
            [
                'name' => 'Sports & Physical Education',
                'description' => 'Footballs, basketballs, volleyball nets, athletic jerseys, lawn care equipment, and campus recreational gear.',
            ],
        ];

        foreach ($categories as $cat) {
            InventoryCategory::updateOrCreate(
                ['name' => $cat['name']],
                ['description' => $cat['description']]
            );
        }
    }
}
