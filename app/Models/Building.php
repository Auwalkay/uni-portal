<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Building extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'building_type',
        'capacity',
        'usable_for_exams',
        'status',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'usable_for_exams' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($building) {
            if (empty($building->code)) {
                $building->code = mb_strtoupper(Str::slug($building->name));
            } else {
                $building->code = mb_strtoupper($building->code);
            }

            if (auth()->check() && empty($building->created_by)) {
                $building->created_by = auth()->id();
            }
        });

        static::updating(function ($building) {
            if (empty($building->code)) {
                $building->code = mb_strtoupper(Str::slug($building->name));
            } else {
                $building->code = mb_strtoupper($building->code);
            }

            if (auth()->check()) {
                $building->updated_by = auth()->id();
            }
        });
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? mb_strtoupper($value) : null,
            set: fn (?string $value) => $value ? mb_strtoupper(trim($value)) : null,
        );
    }

    public function scopeUsableForExams($query)
    {
        return $query->where('usable_for_exams', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
