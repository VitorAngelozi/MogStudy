<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyRoomParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_room_id',
        'user_id',
        'joined_at',
        'left_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(StudyRoom::class, 'study_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationLabelAttribute(): string
    {
        return gmdate('H:i:s', (int) ($this->duration_seconds ?? 0));
    }
}
