<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicantReferee extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'academic_capacity_rating' => 'integer',
        'research_potential_rating' => 'integer',
        'character_integrity_rating' => 'integer',
        'communication_rating' => 'integer',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const RECOMMENDATION_STRENGTHS = [
        'strongly_recommend' => 'Strongly Recommend',
        'recommend' => 'Recommend',
        'recommend_with_reservation' => 'Recommend with Reservation',
        'do_not_recommend' => 'Do Not Recommend',
    ];

    protected static function booted(): void
    {
        static::creating(function (ApplicantReferee $referee) {
            if (empty($referee->token)) {
                $referee->token = Str::random(64);
            }
            if (empty($referee->status)) {
                $referee->status = 'pending';
            }
            if (empty($referee->expires_at)) {
                $referee->expires_at = now()->addDays(30);
            }
        });
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast() && !$this->isSubmitted();
    }

    public function getAverageRatingAttribute(): ?float
    {
        $ratings = array_filter([
            $this->academic_capacity_rating,
            $this->research_potential_rating,
            $this->character_integrity_rating,
            $this->communication_rating,
        ], fn ($r) => $r !== null);

        if (empty($ratings)) {
            return null;
        }

        return round(array_sum($ratings) / count($ratings), 2);
    }

    public function getRecommendationLabelAttribute(): string
    {
        return self::RECOMMENDATION_STRENGTHS[$this->recommendation_strength]
            ?? ucfirst(str_replace('_', ' ', (string) $this->recommendation_strength));
    }
}

