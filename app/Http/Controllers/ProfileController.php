<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\User;
use App\Support\ActivityHeatmap;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        $logs = $user->dailyLogs()
            ->latest('log_date')
            ->latest('created_at')
            ->limit(12)
            ->get();

        $sessions = $user->studySessions()
            ->latest('started_at')
            ->limit(8)
            ->get();

        $heatmap = app(ActivityHeatmap::class)->build($user->id);

        return view('profile', [
            'profileUser' => $user,
            'logs' => $logs,
            'sessions' => $sessions,
            'heatmap' => $heatmap,
            'readme' => $user->readme_markdown ?: $user->defaultReadmeTemplate(),
            'streak' => $this->buildStreak($user->id),
        ]);
    }

    public function updateReadme(Request $request)
    {
        $data = $request->validate([
            'readme_markdown' => ['required', 'string', 'max:500'],
        ], [
            'readme_markdown.max' => 'O README pode ter no maximo 500 caracteres.',
        ]);

        $request->user()->forceFill([
            'readme_markdown' => trim($data['readme_markdown']),
        ])->save();

        return redirect()->route('dashboard')->with('status', 'README atualizado com sucesso.');
    }

    private function buildStreak(int $userId): int
    {
        $dates = DailyLog::query()
            ->where('user_id', $userId)
            ->pluck('log_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        $streak = 0;
        $cursor = now()->startOfDay();

        if (! $dates->contains($cursor->toDateString())) {
            $cursor = $cursor->subDay();
        }

        while ($dates->contains($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

}
