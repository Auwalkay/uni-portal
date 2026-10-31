<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'building_id',
        'staff_id',
        'quantity',
        'location_details',
        'assignable_type',
        'assignable_id',
        'assigned_at',
        'expected_return_date',
        'returned_at',
        'status',
        'condition_on_assignment',
        'condition_on_return',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'assigned_at' => 'datetime',
        'expected_return_date' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
