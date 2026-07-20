<?php

namespace App\Http\Controllers;

use App\Models\StudyFocusParticipation;
use App\Models\StudySession;
use App\Models\StudySubject;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    public function store(Request $request)
    {
        $request->merge([
            'study_subject_name' => trim((string) $request->input('study_subject_name')),
        ]);

        $data = $request->validate([
            'study_subject_id' => ['nullable', 'integer'],
            'study_subject_name' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'study_subject_name.required' => 'Escolha uma materia cadastrada.',
        ]);

        $studySubject = StudySubject::query()
            ->where('user_id', $request->user()->id)
            ->when(
                isset($data['study_subject_id']),
                fn ($query) => $query->where('id', $data['study_subject_id']),
                fn ($query) => $query->where('name', $data['study_subject_name'])
            )
            ->first();

        if (! $studySubject) {
            return back()
                ->withErrors([
                    'study_subject_name' => 'Escolha uma materia cadastrada antes de iniciar.',
                ])
                ->withInput();
        }

        $hasRunningSession = $request->user()->studySessions()
            ->whereNull('ended_at')
            ->exists();

        if ($hasRunningSession) {
            return back()->withErrors([
                'study_subject_name' => 'Voce ja tem uma sessao em andamento.',
            ]);
        }

        StudySession::create([
            'user_id' => $request->user()->id,
            'study_subject_id' => $studySubject->id,
            'subject' => $studySubject->name,
            'notes' => $data['notes'] ?: null,
            'started_at' => now(),
            'duration_seconds' => 0,
        ]);

        return redirect()->route('dashboard')->with('status', 'Sessão iniciada com sucesso.');
    }

    public function stop(Request $request, StudySession $studySession)
    {
        abort_unless($studySession->user_id === $request->user()->id, 403);

        if ($studySession->ended_at) {
            return back()->withErrors([
                'session' => 'Essa sessão já foi encerrada.',
            ]);
        }

        $endedAt = now();
        $durationSeconds = max(1, $studySession->effectiveElapsedSeconds());

        $studySession->forceFill([
            'ended_at' => $endedAt,
            'paused_at' => null,
            'duration_seconds' => $durationSeconds,
        ])->save();

        StudyFocusParticipation::query()
            ->where('study_session_id', $studySession->id)
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->update([
                'ended_at' => $endedAt,
                'paused_at' => null,
                'paused_seconds' => (int) $studySession->paused_seconds,
                'duration_seconds' => $durationSeconds,
                'status' => StudyFocusParticipation::STATUS_COMPLETED,
                'updated_at' => now(),
            ]);

        return redirect()->route('dashboard')->with('status', 'Sessão encerrada e salva no histórico.');
    }

    public function pause(Request $request, StudySession $studySession)
    {
        abort_unless($studySession->user_id === $request->user()->id, 403);

        if ($studySession->ended_at || $studySession->paused_at) {
            return back();
        }

        $pausedAt = now();

        $studySession->forceFill([
            'paused_at' => $pausedAt,
        ])->save();

        StudyFocusParticipation::query()
            ->where('study_session_id', $studySession->id)
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->update([
                'paused_at' => $pausedAt,
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Cronometro pausado.');
    }

    public function resume(Request $request, StudySession $studySession)
    {
        abort_unless($studySession->user_id === $request->user()->id, 403);

        if ($studySession->ended_at || ! $studySession->paused_at) {
            return back();
        }

        $resumedAt = now();
        $pauseSeconds = max(0, (int) round($studySession->paused_at->diffInSeconds($resumedAt)));
        $pausedSeconds = (int) $studySession->paused_seconds + $pauseSeconds;

        $studySession->forceFill([
            'paused_at' => null,
            'paused_seconds' => $pausedSeconds,
        ])->save();

        StudyFocusParticipation::query()
            ->where('study_session_id', $studySession->id)
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->update([
                'paused_at' => null,
                'paused_seconds' => $pausedSeconds,
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Cronometro retomado.');
    }
}
