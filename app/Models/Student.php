<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'matriculation_number',
        'department_id',
        'faculty_id',
        'program_id',
        'admitted_session_id',
        'program_duration',
        'current_level',
        'status',
        'gender',
        'dob',
        'phone_number',
        'address',
        'entry_mode',
        'state_id',
        'lga_id',
        'passport_photo_path',
        'indigene_letter_path',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_address',
        'next_of_kin_relationship',
    ];

    protected $casts = [
        'program_duration' => 'integer',
    ];

    public function admittedSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'admitted_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function program()
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }

    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function lga()
    {
        return $this->belongsTo(Lga::class);
    }

    public function oLevelResults()
    {
        return $this->hasMany(OLevelResult::class);
    }
}
