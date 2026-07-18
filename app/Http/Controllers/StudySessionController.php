<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $hasRunningSession = $request->user()->studySessions()
            ->whereNull('ended_at')
            ->exists();

        if ($hasRunningSession) {
            return back()->withErrors([
                'subject' => 'Você já tem uma sessão em andamento.',
            ]);
        }

        StudySession::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'] ?: 'Sessão de estudo',
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

        $studySession->forceFill([
            'ended_at' => $endedAt,
            'duration_seconds' => max(1, $studySession->started_at->diffInSeconds($endedAt)),
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Sessão encerrada e salva no histórico.');
    }
}
