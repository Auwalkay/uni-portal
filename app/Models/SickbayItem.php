<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SickbayItem extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = ['name', 'category', 'stock_quantity', 'alert_threshold', 'expiry_date'];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
