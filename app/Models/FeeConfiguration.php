<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeConfiguration extends Model
{
    protected $fillable = [
        'fee_type_id',
        'session_id',
        'faculty_id',
        'department_id',
        'program_id',
        'level',
        'amount',
        'is_compulsory'
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function program()
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }
}
