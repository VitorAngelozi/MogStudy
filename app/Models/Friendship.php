<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    public function involves(int $userId): bool
    {
        return $this->requester_id === $userId || $this->addressee_id === $userId;
    }
}
