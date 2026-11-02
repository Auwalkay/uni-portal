<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostgraduateExternalExaminerRegistry extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'postgraduate_external_examiners_registry';
    protected $guarded = ['id'];

    protected $casts = [
        'consecutive_sessions_count' => 'integer',
        'total_theses_examined' => 'integer',
        'is_active' => 'boolean',
    ];

    public function firstAppointedSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'first_appointed_session_id');
    }

    public function lastAppointedSession(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'last_appointed_session_id');
    }

    public function isTenureWarning(): bool
    {
        return $this->consecutive_sessions_count >= 3;
    }

    public function recordExamination(Session $session): void
    {
        $consecutive = $this->consecutive_sessions_count;
        if ($this->last_appointed_session_id && $this->last_appointed_session_id !== $session->id) {
            $consecutive++;
        }

        $this->update([
            'last_appointed_session_id' => $session->id,
            'consecutive_sessions_count' => $consecutive,
            'total_theses_examined' => $this->total_theses_examined + 1,
        ]);
    }
}

