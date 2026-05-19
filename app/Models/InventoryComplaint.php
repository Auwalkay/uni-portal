<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryComplaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'inventory_assignment_id',
        'inventory_item_id',
        'subject',
        'description',
        'status',
        'admin_notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignment()
    {
        return $this->belongsTo(InventoryAssignment::class, 'inventory_assignment_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
