<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Student extends Model
{
    use HasUuids, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
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

        static::saved(function ($student) {
            static::clearStatsCache();
        });

        static::deleted(function ($student) {
            static::clearStatsCache();
        });
    }

    public static function clearStatsCache()
    {
        \Illuminate\Support\Facades\Cache::forget('students_stats_admin');
        if (auth()->check()) {
            \Illuminate\Support\Facades\Cache::forget('students_stats_' . auth()->id());
        }
    }

    protected function firstName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? mb_strtoupper($value, 'UTF-8') : null,
            set: fn (?string $value) => $value ? mb_strtoupper(trim($value), 'UTF-8') : null,
        );
    }

    protected function lastName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? mb_strtoupper($value, 'UTF-8') : null,
            set: fn (?string $value) => $value ? mb_strtoupper(trim($value), 'UTF-8') : null,
        );
    }

    protected function middleName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? mb_strtoupper($value, 'UTF-8') : null,
            set: fn (?string $value) => $value ? mb_strtoupper(trim($value), 'UTF-8') : null,
        );
    }

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
        'jamb_registration_number',
        'jamb_score',
        'previous_institution',
        'state_id',
        'lga_id',
        'passport_photo_path',
        'indigene_letter_path',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_address',
        'next_of_kin_relationship',
        'scholarship_id',
        'fee_policy',
        'pending_promotion_session_id',
        'created_by',
        'updated_by',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class, 'program_id');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
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

    public function currentSession()
    {
        return $this->hasOne(StudentSession::class)->where('status', 'active')->latest();
    }

    public function hostelBookings()
    {
        return $this->hasMany(HostelBooking::class);
    }

    public function sessions()
    {
        return $this->hasMany(StudentSession::class);
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getMatricNoAttribute()
    {
        return $this->attributes['matriculation_number'] ?? $this->attributes['matric_number'] ?? null;
    }

    public function getMatricNumberAttribute()
    {
        return $this->attributes['matriculation_number'] ?? $this->attributes['matric_no'] ?? null;
    }

    /**
     * Check if the student has cleared previous session fees and promote them.
     */
    public function checkAndPromoteStudent()
    {
        if ($this->pending_promotion_session_id) {
            $targetSession = \App\Models\Session::find($this->pending_promotion_session_id);
            if ($targetSession) {
                $previousSession = \App\Models\Session::where('start_date', '<', $targetSession->start_date)
                    ->orderBy('start_date', 'desc')
                    ->first();
                
                $hasUnpaid = false;
                if ($previousSession) {
                    $hasUnpaid = \App\Models\Invoice::where('user_id', $this->user_id)
                        ->where('session_id', $previousSession->id)
                        ->where('type', 'school_fee')
                        ->where('status', '!=', 'paid')
                        ->exists();
                }

                if (!$hasUnpaid) {
                    $currentSemesterName = $targetSession->semesters()->where('is_current', true)->value('name')
                        ?? $targetSession->semesters()->where('name', 'First Semester')->value('name')
                        ?? 'First Semester';
                    
                    // Clear the pending promotion flag
                    $this->update(['pending_promotion_session_id' => null]);
                    
                    // Dispatch the promotion job
                    \App\Jobs\Academic\ProcessStudentSessionJob::dispatch($this, $targetSession, $currentSemesterName);
                }
            }
        }
    }
}
