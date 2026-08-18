<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HostelBooking extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'session_id',
        'hostel_room_id',
        'invoice_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getStatusAttribute($value)
    {
        $currentSession = \App\Models\Session::current();
        if ($currentSession && $this->session_id !== $currentSession->id && in_array($value, ['pending', 'confirmed'])) {
            return 'expired';
        }
        return $value;
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function room()
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
