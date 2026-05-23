<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class InventoryItem extends Model
{
    use HasFactory, LogsActivity;

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
    ];

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function assignments()
    {
        return $this->hasMany(InventoryAssignment::class);
    }
}
