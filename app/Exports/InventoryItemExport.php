<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryItemExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventoryItem::with('category')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category?->name ?? 'N/A',
                'sku' => $item->sku,
                'total_quantity' => $item->total_quantity,
                'available_quantity' => $item->available_quantity,
                'condition' => $item->condition,
                'created_at' => $item->created_at->toDateTimeString(),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Category',
            'SKU',
            'Total Quantity',
            'Available Quantity',
            'Condition',
            'Created At',
        ];
    }
}
