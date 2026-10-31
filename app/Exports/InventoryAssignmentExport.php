<?php

namespace App\Exports;

use App\Models\InventoryAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryAssignmentExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventoryAssignment::with(['item', 'building', 'staff.user', 'staff.department', 'assignable'])->get()->map(function ($assignment) {
            $destType = 'Campus Building';
            $destination = 'N/A';

            if ($assignment->building) {
                $destType = 'Campus Building';
                $destination = $assignment->building->name . ($assignment->building->code ? " ({$assignment->building->code})" : '');
            } elseif ($assignment->staff) {
                $destType = 'Staff Custody';
                $destination = ($assignment->staff->user?->name ?? 'Staff') .
                    ($assignment->staff->staff_number ? " [{$assignment->staff->staff_number}]" : '') .
                    ($assignment->staff->department ? " - {$assignment->staff->department->name}" : '');
            } elseif ($assignment->assignable) {
                $destType = 'Staff Custody';
                $destination = $assignment->assignable->user?->name ?? 'Staff';
            }

            return [
                'id' => $assignment->id,
                'item_name' => $assignment->item?->name ?? 'N/A',
                'sku' => $assignment->item?->sku ?? 'N/A',
                'target_type' => $destType,
                'destination' => $destination,
                'location_details' => $assignment->location_details ?? 'N/A',
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
            'Target Type',
            'Destination / Custodian',
            'Room / Location Notes',
            'Quantity',
            'Assigned / Deployed At',
            'Expected Return Date',
            'Status',
        ];
    }
}
