<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Scholarship extends Model
{
    use HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected static function booted()
    {
        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $fillable = [
        'name',
        'type',
        'percentage',
        'amount',
        'covers_admin_charges',
        'covers_hostel_fees',
        'covers_all_expenses',
        'waives_late_fees',
        'excluded_fee_type_ids',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'covers_admin_charges' => 'boolean',
        'covers_hostel_fees' => 'boolean',
        'covers_all_expenses' => 'boolean',
        'waives_late_fees' => 'boolean',
        'excluded_fee_type_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function isFeeTypeExcluded($feeTypeId): bool
    {
        if (empty($feeTypeId) || empty($this->excluded_fee_type_ids) || !is_array($this->excluded_fee_type_ids)) {
            return false;
        }

        $target = (string) $feeTypeId;
        foreach ($this->excluded_fee_type_ids as $id) {
            if ((string) $id === $target) {
                return true;
            }
        }

        return false;
    }

    public function isComprehensive(): bool
    {
        return (bool) $this->covers_all_expenses;
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
