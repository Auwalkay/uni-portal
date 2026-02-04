<?php

namespace App\Models\FrontDesk;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasUuids;

    protected $table = 'front_desk_complaints';

    protected $fillable = [
        'reference_id',
        'complainant_name',
        'phone',
        'subject',
        'description',
        'status',
        'resolution_notes',
        'receptionist_id',
    ];

    protected static function booted()
    {
        static::creating(function ($complaint) {
            $count = static::whereYear('created_at', now()->year)->count() + 1;
            $complaint->reference_id = 'CMP-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }
}
