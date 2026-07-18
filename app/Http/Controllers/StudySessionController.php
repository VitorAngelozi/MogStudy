<?php

namespace App\Http\Controllers;

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
        $durationSeconds = max(1, (int) round($studySession->started_at->diffInSeconds($endedAt)));

        $studySession->forceFill([
            'ended_at' => $endedAt,
            'duration_seconds' => $durationSeconds,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Sessão encerrada e salva no histórico.');
    }
}
