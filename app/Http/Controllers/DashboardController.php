<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $currentSession = $user->studySessions()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $recentSessions = $user->studySessions()
            ->latest('started_at')
            ->limit(6)
            ->get();

        $recentLogs = $user->dailyLogs()
            ->latest('log_date')
            ->latest('created_at')
            ->limit(7)
            ->get();

        $todayLog = $user->dailyLogs()
            ->whereDate('log_date', $today)
            ->first();

        $totals = [
            'minutes_today' => (int) $user->studySessions()
                ->whereDate('started_at', $today)
                ->sum('duration_seconds'),
            'sessions_total' => $user->studySessions()->count(),
            'logs_total' => $user->dailyLogs()->count(),
            'minutes_total' => (int) $user->studySessions()->sum('duration_seconds'),
        ];

        $subjects = $this->buildSubjects($recentSessions, $recentLogs, $totals);
        $heatmap = $this->buildHeatmap($user->id);
        $goal = $this->buildGoal($totals['minutes_today']);
        $profile = $this->buildProfileSummary($user, $totals);
        $recentActivity = $this->buildRecentActivity($user, $recentSessions, $recentLogs);
        $achievements = $this->buildAchievements($recentLogs);
        $friends = $this->buildFriends();
        $sidebarItems = $this->buildSidebarItems($user);
        $metrics = $this->buildMetrics($totals, count($subjects));

        $timer = [
            'state' => $currentSession ? 'running' : 'idle',
            'subject' => $currentSession?->subject ?: 'Laravel',
            'started_at' => $currentSession?->started_at?->toIso8601String(),
            'elapsed_seconds' => $currentSession
                ? $currentSession->started_at->diffInSeconds(now())
                : 5143,
            'display' => $currentSession
                ? $this->formatTimer($currentSession->started_at->diffInSeconds(now()))
                : '01:25:43',
        ];

        return view('dashboard', [
            'user' => $user,
            'currentSession' => $currentSession,
            'recentSessions' => $recentSessions,
            'recentLogs' => $recentLogs,
            'todayLog' => $todayLog,
            'profile' => $profile,
            'sidebarItems' => $sidebarItems,
            'timer' => $timer,
            'goal' => $goal,
            'heatmap' => $heatmap,
            'subjects' => $subjects,
            'recentActivity' => $recentActivity,
            'achievements' => $achievements,
            'friends' => $friends,
            'streak' => $this->buildStreak($user->id),
            'greeting' => $this->greetingForHour(now()->hour),
            'heroSubtitle' => $currentSession
                ? 'Pronto para continuar a sessao em andamento?'
                : 'Pronto para mais uma sessao de foco?',
            'totals' => [
                'minutes_today' => $totals['minutes_today'],
                'minutes_total' => $totals['minutes_total'],
                'sessions_total' => $totals['sessions_total'],
                'logs_total' => $totals['logs_total'],
                'hours_total_label' => $this->formatMinutesAsHours($totals['minutes_total']),
                'today_label' => $this->formatMinutesAsHours($totals['minutes_today']),
            ],
            'metrics' => $metrics,
        ]);
    }

    private function buildProfileSummary(User $user, array $totals): array
    {
        $experience = ($totals['minutes_total'] * 8) + ($totals['sessions_total'] * 35) + ($totals['logs_total'] * 25);
        $level = max(1, intdiv($experience, 500) + 1);
        $xpCurrent = $experience % 500;

        return [
            'avatar' => Str::upper(Str::substr($user->displayName(), 0, 1)),
            'display_name' => $user->displayName(),
            'username' => $user->username,
            'bio' => $user->bio ?: 'Sem bio ainda. Adicione uma descricao no perfil.',
            'level' => $level,
            'xp' => $experience,
            'xp_current' => $xpCurrent,
            'xp_goal' => 500,
            'xp_percent' => min(100, (int) round(($xpCurrent / 500) * 100)),
            'readme_words' => str_word_count(strip_tags($user->readme_markdown ?: $user->defaultReadmeTemplate())),
        ];
    }

    private function buildHeatmap(int $userId): array
    {
        $cells = [];

        for ($offset = 41; $offset >= 0; $offset--) {
            $date = now()->startOfDay()->subDays($offset);
            $minutes = (int) DailyLog::query()
                ->where('user_id', $userId)
                ->whereDate('log_date', $date)
                ->sum('study_minutes');

            $cells[] = [
                'date' => $date->toDateString(),
                'month' => $date->format('M'),
                'minutes' => $minutes,
                'level' => $this->contributionLevel($minutes),
            ];
        }

        return [
            'rows' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'],
            'months' => $this->buildHeatmapMonths($cells),
            'cells' => $cells,
        ];
    }

    private function buildHeatmapMonths(array $cells): array
    {
        return collect($cells)
            ->chunk(7)
            ->map(function ($week) {
                $date = Carbon::parse($week->first()['date']);

                return [
                    'label' => $this->shortMonthLabel($date),
                    'date' => $date->toDateString(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildGoal(int $minutesToday): array
    {
        $target = 360;
        $progress = min(100, (int) round(($minutesToday / max(1, $target)) * 100));

        return [
            'title' => 'Meta diaria',
            'done_minutes' => $minutesToday,
            'target_minutes' => $target,
            'remaining_minutes' => max(0, $target - $minutesToday),
            'progress' => $progress,
            'done_label' => $this->formatMinutesAsHours($minutesToday),
            'target_label' => $this->formatMinutesAsHours($target),
            'remaining_label' => $this->formatMinutesAsHours(max(0, $target - $minutesToday)),
            'bars' => [2, 4, 6, 7, 10, 14, 8, 11, 15, 9, 12, 16, 7, 5, 4, 10, 13, 6, 9, 12, 11, 7, 5, 3],
        ];
    }

    private function buildMetrics(array $totals, int $subjectsTotal): array
    {
        return [
            [
                'label' => 'Horas estudadas',
                'value' => $this->formatMinutesAsHours($totals['minutes_total']),
                'icon' => 'clock',
                'tone' => 'violet',
                'subtext' => 'tempo acumulado',
            ],
            [
                'label' => 'Sessoes',
                'value' => $totals['sessions_total'],
                'icon' => 'calendar',
                'tone' => 'emerald',
                'subtext' => 'registros fechados',
            ],
            [
                'label' => 'Materias',
                'value' => $subjectsTotal,
                'icon' => 'book',
                'tone' => 'cyan',
                'subtext' => 'areas em foco',
            ],
            [
                'label' => 'Meta semanal',
                'value' => '92%',
                'icon' => 'trophy',
                'tone' => 'amber',
                'subtext' => 'ritmo consistente',
            ],
        ];
    }

    private function buildSidebarItems(User $user): array
    {
        return [
            ['label' => 'Inicio', 'href' => '#inicio', 'icon' => 'home', 'active' => true],
            ['label' => 'Materias', 'href' => '#materias', 'icon' => 'book', 'active' => false],
            ['label' => 'Sessoes', 'href' => '#sessoes', 'icon' => 'clock', 'active' => false],
            ['label' => 'Projetos', 'href' => '#projetos', 'icon' => 'folder', 'active' => false],
            ['label' => 'Anotacoes', 'href' => '#anotacoes', 'icon' => 'notes', 'active' => false],
            ['label' => 'Metas', 'href' => '#metas', 'icon' => 'target', 'active' => false],
            ['label' => 'Conquistas', 'href' => '#conquistas', 'icon' => 'trophy', 'active' => false],
            ['label' => 'Amigos', 'href' => '#amigos', 'icon' => 'users', 'active' => false],
            ['label' => 'Ranking', 'href' => '#ranking', 'icon' => 'chart', 'active' => false],
            ['label' => 'Configuracoes', 'href' => route('profile.show', $user), 'icon' => 'settings', 'active' => false],
        ];
    }

    private function buildSubjects($recentSessions, $recentLogs, array $totals): array
    {
        $seed = max(1, (int) floor($totals['minutes_total'] / 60));
        $firstSubject = $recentSessions->first()?->subject ?: 'Laravel';
        $lastTopic = $recentLogs->first()?->title ?: 'Banco de dados';

        return [
            [
                'name' => $firstSubject,
                'minutes' => 324 + ($seed * 3),
                'hours_label' => $this->formatHoursCount(324 + ($seed * 3)),
                'progress' => 75,
                'tone' => 'violet',
                'icon' => 'laravel',
            ],
            [
                'name' => 'Java',
                'minutes' => 210 + ($seed * 2),
                'hours_label' => $this->formatHoursCount(210 + ($seed * 2)),
                'progress' => 60,
                'tone' => 'cyan',
                'icon' => 'java',
            ],
            [
                'name' => 'Banco de Dados',
                'minutes' => 180 + $seed,
                'hours_label' => $this->formatHoursCount(180 + $seed),
                'progress' => 45,
                'tone' => 'amber',
                'icon' => 'database',
            ],
            [
                'name' => $lastTopic,
                'minutes' => 140 + $seed,
                'hours_label' => $this->formatHoursCount(140 + $seed),
                'progress' => 40,
                'tone' => 'emerald',
                'icon' => 'code',
            ],
            [
                'name' => 'Ingles',
                'minutes' => 98 + $seed,
                'hours_label' => $this->formatHoursCount(98 + $seed),
                'progress' => 30,
                'tone' => 'indigo',
                'icon' => 'language',
            ],
        ];
    }

    private function buildRecentActivity(User $user, $recentSessions, $recentLogs): array
    {
        $items = [];

        if ($recentLogs->isNotEmpty()) {
            $log = $recentLogs->first();
            $items[] = [
                'avatar' => $this->avatarFromName($user->displayName()),
                'title' => 'Voce estudou ' . $log->title,
                'detail' => Str::limit($log->content, 48),
                'when' => $log->created_at ? $log->created_at->diffForHumans() : $log->log_date->diffForHumans(),
                'accent' => 'violet',
            ];
        }

        if ($recentSessions->isNotEmpty()) {
            $session = $recentSessions->first();
            $items[] = [
                'avatar' => 'S',
                'title' => 'Sessao criada em ' . $session->subject,
                'detail' => $this->formatTimer((int) $session->duration_seconds) . ' · ' . ($session->notes ?: 'sem observacoes'),
                'when' => $session->started_at->diffForHumans(),
                'accent' => 'emerald',
            ];
        }

        $items[] = [
            'avatar' => 'M',
            'title' => 'Maria completou um curso de SQL',
            'detail' => 'Parabens pela conquista!',
            'when' => 'ha 5h',
            'accent' => 'amber',
        ];

        $items[] = [
            'avatar' => 'L',
            'title' => 'Lucas criou uma nova sessao de foco',
            'detail' => '4h de Java e mais um bloco fechado.',
            'when' => 'ha 7h',
            'accent' => 'cyan',
        ];

        return array_slice($items, 0, 4);
    }

    private function buildAchievements($recentLogs): array
    {
        return [
            [
                'icon' => 'fire',
                'title' => max(3, $recentLogs->count()) . ' dias consecutivos',
                'detail' => 'Estude por varios dias seguidos',
                'when' => 'ha 1d',
                'tone' => 'fire',
            ],
            [
                'icon' => 'medal',
                'title' => '100 horas',
                'detail' => 'Acumule 100 horas de estudos',
                'when' => 'ha 3d',
                'tone' => 'gold',
            ],
            [
                'icon' => 'sun',
                'title' => 'Madrugador',
                'detail' => 'Estude antes das 7h',
                'when' => 'ha 5d',
                'tone' => 'sun',
            ],
        ];
    }

    private function buildFriends(): array
    {
        return [
            ['name' => 'Lucas', 'status' => 'Estudando Java', 'online' => true, 'avatar' => 'L'],
            ['name' => 'Maria', 'status' => 'Acudando SQL', 'online' => true, 'avatar' => 'M'],
            ['name' => 'Pedro', 'status' => 'Em uma sessao de foco', 'online' => true, 'avatar' => 'P'],
        ];
    }

    private function greetingForHour(int $hour): string
    {
        return match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
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

    private function avatarFromName(string $name): string
    {
        return Str::upper(Str::substr($name, 0, 1));
    }

    private function formatMinutesAsHours(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours === 0) {
            return $remaining.'m';
        }

        if ($remaining === 0) {
            return $hours.'h';
        }

        return $hours.'h'.$remaining;
    }

    private function formatHoursCount(int $minutes): string
    {
        return intdiv($minutes, 60).'h estudadas';
    }

    private function formatTimer(int $seconds): string
    {
        return gmdate('H:i:s', max(0, $seconds));
    }

    private function shortMonthLabel(Carbon $date): string
    {
        return match ((int) $date->format('n')) {
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            default => 'Dez',
        };
    }
}
