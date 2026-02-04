<?php

namespace App\Models\FrontDesk;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    use HasUuids;

    protected $table = 'front_desk_enquiries';

    protected $fillable = [
        'reference_id',
        'inquirer_name',
        'phone',
        'email',
        'inquiry',
        'response',
        'status',
        'receptionist_id',
    ];

    protected static function booted()
    {
        static::creating(function ($enquiry) {
            $count = static::whereYear('created_at', now()->year)->count() + 1;
            $enquiry->reference_id = 'ENQ-' . now()->year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }
}
