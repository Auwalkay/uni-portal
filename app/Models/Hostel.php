<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Hostel extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'name',
        'gender_type',
        'description',
        'payment_gateway',
        'squadco_secret_key',
        'squadco_public_key',
        'paystack_secret_key',
        'paystack_public_key',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function blocks()
    {
        return $this->hasMany(HostelBlock::class);
    }

    public function floors()
    {
        return $this->hasManyThrough(HostelFloor::class, HostelBlock::class);
    }

    public function fees()
    {
        return $this->hasMany(HostelFee::class);
    }

    public function getSecretKeyForGateway(string $gateway): ?string
    {
        return match (strtolower($gateway)) {
            'paystack' => $this->paystack_secret_key,
            'squadco' => $this->squadco_secret_key,
            default => null,
        };
    }

    public function getPublicKeyForGateway(string $gateway): ?string
    {
        return match (strtolower($gateway)) {
            'paystack' => $this->paystack_public_key,
            'squadco' => $this->squadco_public_key,
            default => null,
        };
    }

    public function hasCustomGatewayConfig(): bool
    {
        return !empty($this->payment_gateway) ||
            !empty($this->squadco_secret_key) ||
            !empty($this->paystack_secret_key);
    }
}
