<?php

namespace App\Exports;

use App\Models\InventoryAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryAssignmentExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventoryAssignment::with(['item', 'assignable.user'])->get()->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'item_name' => $assignment->item?->name ?? 'N/A',
                'sku' => $assignment->item?->sku ?? 'N/A',
                'assigned_to' => $assignment->assignable?->user?->name ?? 'N/A',
                'assigned_at' => $assignment->assigned_at,
                'expected_return_date' => $assignment->expected_return_date,
                'status' => $assignment->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Item Name',
            'SKU',
            'Assigned To',
            'Assigned At',
            'Expected Return Date',
            'Status',
        ];
    }
}
