<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_date',
        'title',
        'content',
        'study_minutes',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'study_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
