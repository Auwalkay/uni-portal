<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Payroll extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $guarded = [];

    protected $casts = [
        'generated_at' => 'datetime',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Recalculate the overall total amount of this payroll based on active items.
     */
    public function recalculateTotal(): void
    {
        $total = (float) $this->items()
            ->where('status', '!=', 'excluded')
            ->sum('net_salary');

        $this->update(['total_amount' => $total]);
    }
}
