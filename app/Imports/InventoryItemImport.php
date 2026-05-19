<?php

namespace App\Imports;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InventoryItemImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $categoryName = $row['category'] ?? 'Uncategorized';
        
        $category = InventoryCategory::firstOrCreate(
            ['name' => $categoryName],
            ['description' => 'Automatically created during import.']
        );

        return new InventoryItem([
            'inventory_category_id' => $category->id,
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'sku' => $row['sku'] ?? null,
            'total_quantity' => $row['quantity'] ?? 0,
            'available_quantity' => $row['quantity'] ?? 0,
            'condition' => $row['condition'] ?? 'new',
        ]);
    }
}
