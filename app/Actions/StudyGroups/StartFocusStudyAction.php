<?php

namespace App\Actions\StudyGroups;

use App\Models\StudyFocusParticipation;
use App\Models\StudyFocusRoom;
use App\Models\StudySession;
use App\Models\StudySubject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartFocusStudyAction
{
    public function execute(StudyFocusRoom $focusRoom, User $user, StudySubject $subject, ?string $notes = null): StudyFocusParticipation
    {
        return DB::transaction(function () use ($focusRoom, $user, $subject, $notes) {
            $focusRoom = StudyFocusRoom::query()
                ->whereKey($focusRoom->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $focusRoom->is_active || ! $focusRoom->group->isActive()) {
                throw ValidationException::withMessages([
                    'room' => 'Essa sala de foco nao aceita novos estudos agora.',
                ]);
            }

            if ($subject->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'study_subject_id' => 'Escolha uma materia cadastrada no seu perfil.',
                ]);
            }

            if ($user->studySessions()->whereNull('ended_at')->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'study_subject_id' => 'Voce ja tem uma sessao em andamento.',
                ]);
            }

            if ($user->studyFocusParticipations()->where('status', StudyFocusParticipation::STATUS_ACTIVE)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'study_subject_id' => 'Voce ja esta estudando em uma sala de foco.',
                ]);
            }

            $session = StudySession::create([
                'user_id' => $user->id,
                'study_subject_id' => $subject->id,
                'study_group_id' => $focusRoom->study_group_id,
                'study_focus_room_id' => $focusRoom->id,
                'subject' => $subject->name,
                'notes' => $notes ?: null,
                'source_type' => 'study_focus_room',
                'started_at' => now(),
                'duration_seconds' => 0,
            ]);

            $participation = StudyFocusParticipation::create([
                'study_focus_room_id' => $focusRoom->id,
                'study_session_id' => $session->id,
                'user_id' => $user->id,
                'study_subject_id' => $subject->id,
                'started_at' => $session->started_at,
                'duration_seconds' => 0,
                'status' => StudyFocusParticipation::STATUS_ACTIVE,
            ]);

            $session->forceFill([
                'source_id' => $participation->id,
            ])->save();

            return $participation;
        });
    }
}
