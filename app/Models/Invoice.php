<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Invoice extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice) {
            if ($invoice->amount > 0 && $invoice->paid_amount > $invoice->amount) {
                $invoice->paid_amount = (float) $invoice->amount;
            }

            if ($invoice->amount > 0 && $invoice->paid_amount >= $invoice->amount) {
                $invoice->status = 'paid';
            }
        });
    }

    protected $guarded = [];

    protected $casts = [
        'amount' => 'double',
        'paid_amount' => 'double',
        'late_fine_applied' => 'boolean',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function booking()
    {
        return $this->hasOne(HostelBooking::class);
    }

    public function studentSession()
    {
        return $this->belongsTo(StudentSession::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getHostel(): ?Hostel
    {
        $booking = $this->relationLoaded('booking') ? $this->booking : $this->booking()->first();
        if ($booking) {
            $booking->loadMissing('room.floor.block.hostel');
            return $booking->room?->floor?->block?->hostel;
        }

        return null;
    }
}
