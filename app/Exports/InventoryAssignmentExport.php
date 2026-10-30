<?php

namespace App\Exports;

use App\Models\InventoryAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryAssignmentExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventoryAssignment::with(['item', 'building', 'assignable'])->get()->map(function ($assignment) {
            $destination = $assignment->building
                ? ($assignment->building->name . ($assignment->building->code ? " ({$assignment->building->code})" : ''))
                : ($assignment->assignable?->user?->name ?? 'N/A');

            return [
                'id' => $assignment->id,
                'item_name' => $assignment->item?->name ?? 'N/A',
                'sku' => $assignment->item?->sku ?? 'N/A',
                'building' => $destination,
                'location_details' => $assignment->location_details ?? 'General',
                'quantity' => $assignment->quantity ?? 1,
                'assigned_at' => $assignment->assigned_at ? $assignment->assigned_at->format('Y-m-d H:i') : 'N/A',
                'expected_return_date' => $assignment->expected_return_date ? $assignment->expected_return_date->format('Y-m-d') : 'N/A',
                'status' => ucfirst($assignment->status),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Item Name',
            'SKU',
            'Campus Building',
            'Room / Location',
            'Quantity',
            'Deployed At',
            'Expected Return Date',
            'Status',
        ];
    }
}
