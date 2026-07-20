<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'username',
        'display_name',
        'profile_title',
        'email',
        'password',
        'bio',
        'profile_photo_path',
        'readme_markdown',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function studySubjects(): HasMany
    {
        return $this->hasMany(StudySubject::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function sentFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function circlePosts(): HasMany
    {
        return $this->hasMany(CirclePost::class);
    }

    public function circlePostReplies(): HasMany
    {
        return $this->hasMany(CirclePostReply::class);
    }

    public function acceptedFriendIds()
    {
        $sent = $this->sentFriendships()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->pluck('addressee_id');
        $received = $this->receivedFriendships()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->pluck('requester_id');

        return $sent->merge($received)->unique()->values();
    }

    public function isCircleMemberWith(User $user): bool
    {
        if ($this->id === $user->id) {
            return true;
        }

        return Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($query) use ($user) {
                $query
                    ->where(function ($query) use ($user) {
                        $query->where('requester_id', $this->id)
                            ->where('addressee_id', $user->id);
                    })
                    ->orWhere(function ($query) use ($user) {
                        $query->where('requester_id', $user->id)
                            ->where('addressee_id', $this->id);
                    });
            })
            ->exists();
    }

    public function displayName(): string
    {
        return $this->display_name ?: $this->username;
    }

    public function profileTitle(): string
    {
        return $this->profile_title ?: $this->displayName();
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path ? '/storage/'.$this->profile_photo_path : null;
    }
}
