<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_subject_id',
        'subject',
        'notes',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
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
        $seconds = (int) ($this->duration_seconds ?? 0);

        return gmdate('H:i:s', $seconds);
    }
}
