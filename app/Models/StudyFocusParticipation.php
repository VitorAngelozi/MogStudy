<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyFocusParticipation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'study_focus_room_id',
        'study_session_id',
        'user_id',
        'study_subject_id',
        'started_at',
        'ended_at',
        'paused_at',
        'paused_seconds',
        'duration_seconds',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    public function focusRoom(): BelongsTo
    {
        return $this->belongsTo(StudyFocusRoom::class, 'study_focus_room_id');
    }

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studySubject(): BelongsTo
    {
        return $this->belongsTo(StudySubject::class);
    }

    public function getDurationLabelAttribute(): string
    {
        return gmdate('H:i:s', (int) ($this->duration_seconds ?? 0));
    }

    public function effectiveElapsedSeconds(): int
    {
        $end = $this->ended_at ?: ($this->paused_at ?: now());
        $pausedSeconds = (int) ($this->paused_seconds ?? 0);

        return max(0, (int) round($this->started_at->diffInSeconds($end)) - $pausedSeconds);
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->paused_at !== null;
    }
}
