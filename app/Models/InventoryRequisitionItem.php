<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_requisition_id',
        'inventory_item_id',
        'requested_quantity',
        'approved_quantity',
        'unit_of_measure',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
    ];

    public function requisition()
    {
        return $this->belongsTo(InventoryRequisition::class, 'inventory_requisition_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
