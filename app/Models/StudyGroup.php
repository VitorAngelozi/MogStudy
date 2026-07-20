<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyGroup extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const VISIBILITY_FRIENDS = 'friends';

    public const VISIBILITY_PASSWORD = 'password';

    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'owner_id',
        'name',
        'code',
        'description',
        'visibility',
        'password_hash',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(StudyGroupMember::class);
    }

    public function focusRooms(): HasMany
    {
        return $this->hasMany(StudyFocusRoom::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function visibilityLabel(): string
    {
        return match ($this->visibility) {
            self::VISIBILITY_FRIENDS => 'Somente amigos',
            self::VISIBILITY_PASSWORD => 'Privado com senha',
            default => 'Publico',
        };
    }
}
