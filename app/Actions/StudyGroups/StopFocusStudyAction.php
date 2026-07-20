<?php

namespace App\Actions\StudyGroups;

use App\Models\StudyFocusParticipation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StopFocusStudyAction
{
    public function execute(StudyFocusParticipation $participation): StudyFocusParticipation
    {
        return DB::transaction(function () use ($participation) {
            $participation = StudyFocusParticipation::query()
                ->with('studySession')
                ->whereKey($participation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($participation->status !== StudyFocusParticipation::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'participation' => 'Esse estudo ja foi finalizado.',
                ]);
            }

            $endedAt = now();
            $durationSeconds = max(1, $participation->effectiveElapsedSeconds());

            if ($participation->studySession && ! $participation->studySession->ended_at) {
                $participation->studySession->forceFill([
                    'ended_at' => $endedAt,
                    'paused_at' => null,
                    'paused_seconds' => (int) $participation->paused_seconds,
                    'duration_seconds' => $durationSeconds,
                ])->save();
            }

            $participation->forceFill([
                'ended_at' => $endedAt,
                'paused_at' => null,
                'duration_seconds' => $durationSeconds,
                'status' => StudyFocusParticipation::STATUS_COMPLETED,
            ])->save();

            return $participation;
        });
    }
}
