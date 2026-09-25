<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Payment extends Model
{
    use HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(fn($payment) => \Illuminate\Support\Facades\Cache::forget("student_results_index_{$payment->user_id}"));
    }

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function isSuccessStatus(?string $status): bool
    {
        if (!$status) {
            return false;
        }
        return in_array(strtolower($status), ['success', 'successful', 'approved', 'completed', 'paid'], true);
    }

    public static function isFailedStatus(?string $status): bool
    {
        if (!$status) {
            return false;
        }
        return in_array(strtolower($status), ['failed', 'cancelled', 'error', 'abandoned', 'declined', 'expired'], true);
    }

    public static function generateReference(string $prefix = 'PAY'): string
    {
        return $prefix . '-' . strtoupper(uniqid());
    }

    public static function generateTransactionId(string $prefix = 'MIUPAY'): string
    {
        return $prefix . date('Y') . strtoupper(\Illuminate\Support\Str::random(8));
    }
}
