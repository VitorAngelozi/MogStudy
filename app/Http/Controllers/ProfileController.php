<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\User;
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

        $contributions = $this->buildContributions($user->id);

        return view('profile', [
            'profileUser' => $user,
            'logs' => $logs,
            'sessions' => $sessions,
            'contributions' => $contributions,
            'readme' => $user->readme_markdown ?: $user->defaultReadmeTemplate(),
            'streak' => $this->buildStreak($user->id),
        ]);
    }

    public function updateReadme(Request $request)
    {
        $data = $request->validate([
            'readme_markdown' => ['required', 'string', 'max:20000'],
        ]);

        $request->user()->forceFill([
            'readme_markdown' => trim($data['readme_markdown']),
        ])->save();

        return redirect()->route('dashboard')->with('status', 'README atualizado com sucesso.');
    }

    private function buildContributions(int $userId): array
    {
        $map = [];

        for ($offset = 41; $offset >= 0; $offset--) {
            $date = now()->startOfDay()->subDays($offset);
            $minutes = (int) DailyLog::query()
                ->where('user_id', $userId)
                ->whereDate('log_date', $date)
                ->sum('study_minutes');

            $map[] = [
                'date' => $date->toDateString(),
                'minutes' => $minutes,
                'level' => $this->contributionLevel($minutes),
            ];
        }

        return $map;
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

    private function contributionLevel(int $minutes): int
    {
        return match (true) {
            $minutes >= 240 => 4,
            $minutes >= 120 => 3,
            $minutes >= 60 => 2,
            $minutes > 0 => 1,
            default => 0,
        };
    }
}
