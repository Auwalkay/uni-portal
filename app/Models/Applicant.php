<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'previous_cgpa' => 'float',
        'graduation_year' => 'integer',
        'work_experience_years' => 'integer',
        'has_name_change' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class, 'program_choice_1');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Programme::class, 'program_choice_1');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function referees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApplicantReferee::class);
    }

    public function qualifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApplicantQualification::class)->orderBy('sort_order');
    }

    public function employments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApplicantEmployment::class)->orderByDesc('is_current')->orderByDesc('start_date');
    }

    public function isPostgraduate(): bool
    {
        if (in_array(strtoupper((string) $this->application_mode), ['PG', 'POSTGRADUATE'], true)) {
            return true;
        }

        return (bool) $this->programme?->isPostgraduate();
    }

    public function hasCompletedRefereeRecommendations(int $minimumRequired = 3): bool
    {
        return $this->referees()->where('status', 'submitted')->count() >= $minimumRequired;
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }
}
