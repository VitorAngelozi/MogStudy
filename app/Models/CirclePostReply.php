<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CirclePostReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'circle_post_id',
        'user_id',
        'body',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CirclePost::class, 'circle_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
