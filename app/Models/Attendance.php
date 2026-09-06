<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Attendance extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'staff_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'source',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
            if (auth()->check() && !$model->updated_by) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['staff_id', 'date', 'clock_in', 'clock_out', 'status', 'source', 'notes', 'created_by', 'updated_by'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
