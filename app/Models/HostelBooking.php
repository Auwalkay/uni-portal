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
    ];

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
}
