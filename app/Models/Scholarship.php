<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Scholarship extends Model
{
    use HasUuids;

    protected static function booted()
    {
        static::saved(fn() => \App\Services\AcademicCacheService::clearAll());
        static::deleted(fn() => \App\Services\AcademicCacheService::clearAll());
    }

    protected $fillable = ['name', 'type', 'percentage', 'amount', 'covers_admin_charges', 'covers_hostel_fees', 'is_active'];

    protected $casts = [
        'amount' => 'decimal:2',
        'covers_admin_charges' => 'boolean',
        'covers_hostel_fees' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
