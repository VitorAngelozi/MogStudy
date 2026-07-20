<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyRoom extends Model
{
    use HasFactory;

    public const VISIBILITY_FRIENDS = 'friends';

    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'owner_id',
        'name',
        'subject',
        'visibility',
        'code',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(StudyRoomParticipant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    public function visibilityLabel(): string
    {
        return $this->visibility === self::VISIBILITY_FRIENDS ? 'Somente amigos' : 'Aberta por codigo';
    }
}
