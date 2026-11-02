<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateStudentProfile extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'entry_cgpa' => 'decimal:2',
        'entry_graduation_year' => 'integer',
        'prior_qualifications_snapshot' => 'array',
        'employment_history_snapshot' => 'array',
        'work_experience_years' => 'integer',
        'official_transcript_received_at' => 'datetime',
        'min_duration_semesters' => 'integer',
        'max_duration_semesters' => 'integer',
        'sessions_enrolled_count' => 'integer',
        'candidacy_expires_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}

