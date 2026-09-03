<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class InventoryItem extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $fillable = [
        'inventory_category_id',
        'name',
        'description',
        'sku',
        'total_quantity',
        'available_quantity',
        'condition',
        'unit_of_measure',
        'min_stock_level',
        'unit_cost',
        'item_type',
        'supplier_name',
        'location',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function assignments()
    {
        return $this->hasMany(InventoryAssignment::class);
    }

    public function stockLogs()
    {
        return $this->hasMany(InventoryStockLog::class)->latest();
    }

    public function requisitionItems()
    {
        return $this->hasMany(InventoryRequisitionItem::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity <= $this->min_stock_level;
    }
}
