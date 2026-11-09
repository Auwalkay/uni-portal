<?php

namespace App\Exports;

use App\Models\Building;
use App\Models\InventoryAssignment;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryAssignmentExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventoryAssignment::with([
            'item',
            'building',
            'staff.user',
            'staff.department',
            'assignable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Staff::class => ['user', 'department'],
                ]);
            },
        ])->get()->map(function ($assignment) {
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
            } elseif ($assignment->assignable instanceof Building) {
                $destType = 'Campus Building';
                $destination = $assignment->assignable->name . ($assignment->assignable->code ? " ({$assignment->assignable->code})" : '');
            } elseif ($assignment->assignable instanceof Staff) {
                $destType = 'Staff Custody';
                $destination = ($assignment->assignable->user?->name ?? 'Staff') .
                    ($assignment->assignable->staff_number ? " [{$assignment->assignable->staff_number}]" : '') .
                    ($assignment->assignable->department ? " - {$assignment->assignable->department->name}" : '');
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
