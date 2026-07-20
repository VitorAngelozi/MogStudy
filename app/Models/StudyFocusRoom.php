<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyFocusRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_group_id',
        'name',
        'description',
        'icon',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(StudyFocusParticipation::class);
    }

    public function activeParticipations(): HasMany
    {
        return $this->participations()->where('status', StudyFocusParticipation::STATUS_ACTIVE);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}
