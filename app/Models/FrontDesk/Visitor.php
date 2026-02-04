<?php

namespace App\Models\FrontDesk;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use HasUuids;

    protected $table = 'front_desk_visitors';

    protected $fillable = [
        'reference_id',
        'visitor_name',
        'phone',
        'purpose',
        'whom_to_see',
        'check_in',
        'check_out',
        'receptionist_id',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($visitor) {
            $count = static::whereYear('created_at', now()->year)->count() + 1;
            $visitor->reference_id = 'VIS-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }
}
