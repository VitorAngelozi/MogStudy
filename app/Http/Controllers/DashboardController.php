<?php

namespace App\Http\Controllers;

use App\Models\CirclePost;
use App\Models\DailyLog;
use App\Models\Friendship;
use App\Models\StudySession;
use App\Models\User;
use App\Support\ActivityHeatmap;
use App\Support\StudySubjectCards;
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

        $studySubjects = $user->studySubjects()
            ->withSum([
                'studySessions as duration_seconds_total' => fn ($query) => $query->whereNotNull('ended_at'),
            ], 'duration_seconds')
            ->withSum([
                'studySessions as duration_seconds_today' => fn ($query) => $query
                    ->whereNotNull('ended_at')
                    ->whereDate('started_at', $today),
            ], 'duration_seconds')
            ->withSum([
                'studySessions as duration_seconds_week' => fn ($query) => $query
                    ->whereNotNull('ended_at')
                    ->whereBetween('started_at', [
                        now()->startOfWeek()->toDateTimeString(),
                        now()->endOfWeek()->toDateTimeString(),
                    ]),
            ], 'duration_seconds')
            ->withMax([
                'studySessions as last_studied_at' => fn ($query) => $query->whereNotNull('ended_at'),
            ], 'started_at')
            ->get()
            ->values();

        $totals = [
            'seconds_today' => (int) $user->studySessions()
                ->whereNotNull('ended_at')
                ->whereDate('started_at', $today)
                ->sum('duration_seconds'),
            'seconds_total' => (int) $user->studySessions()
                ->whereNotNull('ended_at')
                ->sum('duration_seconds'),
            'sessions_total' => $user->studySessions()->count(),
            'logs_total' => $user->dailyLogs()->count(),
        ];

        $subjectCards = app(StudySubjectCards::class);
        $subjects = $subjectCards->build($subjectCards->sortByRecentActivity($studySubjects)->take(3));
        $heatmap = app(ActivityHeatmap::class)->build($user->id);
        $goal = $this->buildGoal($studySubjects);
        $profile = $this->buildProfileSummary($user, $totals);
        $recentActivity = $this->buildRecentActivity($user, $recentSessions, $recentLogs);
        $achievements = $this->buildAchievements($recentLogs);
        $circle = $this->buildCircle($user);
        $friendNotifications = $this->buildFriendNotifications($user);
        $sidebarItems = $this->buildSidebarItems($user);
        $metrics = $this->buildMetrics($totals, $studySubjects->count());
        $currentSessionBaseSeconds = $currentSession
            ? $this->finishedSecondsTodayForCurrentSubject($user, $currentSession, $today)
            : 0;
        $currentSessionLiveSeconds = $currentSession
            ? $currentSession->started_at->diffInSeconds(now())
            : 0;
        $timerElapsedSeconds = $currentSession
            ? $currentSessionBaseSeconds + $currentSessionLiveSeconds
            : $totals['seconds_today'];

        $timer = [
            'state' => $currentSession ? 'running' : 'idle',
            'subject' => $currentSession?->subject ?: 'Total estudado hoje',
            'started_at' => $currentSession?->started_at?->toIso8601String(),
            'base_seconds' => $currentSessionBaseSeconds,
            'elapsed_seconds' => $timerElapsedSeconds,
            'display' => $this->formatTimer($timerElapsedSeconds),
        ];

        return view('dashboard', [
            'user' => $user,
            'currentSession' => $currentSession,
            'recentSessions' => $recentSessions,
            'recentLogs' => $recentLogs,
            'todayLog' => $todayLog,
            'studySubjects' => $studySubjects,
            'profile' => $profile,
            'sidebarItems' => $sidebarItems,
            'timer' => $timer,
            'goal' => $goal,
            'heatmap' => $heatmap,
            'subjects' => $subjects,
            'recentActivity' => $recentActivity,
            'achievements' => $achievements,
            'circle' => $circle,
            'friendNotifications' => $friendNotifications,
            'streak' => $this->buildStreak($user->id),
            'greeting' => $this->greetingForHour(now()->hour),
            'heroSubtitle' => $currentSession
                ? 'Pronto para continuar a sessao em andamento?'
                : 'Pronto para mais uma sessao de foco?',
            'totals' => [
                'minutes_today' => intdiv($totals['seconds_today'], 60),
                'minutes_total' => intdiv($totals['seconds_total'], 60),
                'seconds_today' => $totals['seconds_today'],
                'seconds_total' => $totals['seconds_total'],
                'sessions_total' => $totals['sessions_total'],
                'logs_total' => $totals['logs_total'],
                'hours_total_label' => $this->formatSecondsAsHours($totals['seconds_total']),
                'today_label' => $this->formatSecondsAsHours($totals['seconds_today']),
            ],
            'metrics' => $metrics,
        ]);
    }

    private function buildProfileSummary(User $user, array $totals): array
    {
        $experience = (intdiv($totals['seconds_total'], 60) * 8) + ($totals['sessions_total'] * 35) + ($totals['logs_total'] * 25);
        $level = max(1, intdiv($experience, 500) + 1);
        $xpCurrent = $experience % 500;

        return [
            'avatar' => Str::upper(Str::substr($user->displayName(), 0, 1)),
            'photo_url' => $user->profilePhotoUrl(),
            'display_name' => $user->displayName(),
            'title' => $user->profileTitle(),
            'username' => $user->username,
            'bio' => $user->bio ?: 'Sem bio ainda. Adicione uma descricao no perfil.',
            'level' => $level,
            'xp' => $experience,
            'xp_current' => $xpCurrent,
            'xp_goal' => 500,
            'xp_percent' => min(100, (int) round(($xpCurrent / 500) * 100)),
        ];
    }

    private function buildGoal($studySubjects): array
    {
        $subjectsWithGoals = $studySubjects->filter(fn ($subject) => (int) ($subject->goal_minutes ?? 0) > 0);
        $targetMinutes = (int) $subjectsWithGoals->sum(fn ($subject) => (int) $subject->goal_minutes);
        $doneSeconds = (int) $subjectsWithGoals->sum(fn ($subject) => (int) ($subject->duration_seconds_week ?? 0));
        $targetSeconds = $targetMinutes * 60;
        $remainingSeconds = max(0, $targetSeconds - $doneSeconds);
        $progress = $targetSeconds > 0
            ? min(100, (int) round(($doneSeconds / $targetSeconds) * 100))
            : 0;

        return [
            'title' => 'Metas semanais',
            'has_goal' => $targetMinutes > 0,
            'done_minutes' => intdiv($doneSeconds, 60),
            'target_minutes' => $targetMinutes,
            'remaining_minutes' => intdiv($remainingSeconds, 60),
            'progress' => $progress,
            'done_label' => $this->formatSecondsAsHours($doneSeconds),
            'target_label' => $this->formatMinutesAsHours($targetMinutes),
            'remaining_label' => $this->formatSecondsAsHours($remainingSeconds),
            'subjects_count' => $subjectsWithGoals->count(),
            'bars' => [2, 4, 6, 7, 10, 14, 8, 11, 15, 9, 12, 16, 7, 5, 4, 10, 13, 6, 9, 12, 11, 7, 5, 3],
        ];
    }

    private function buildMetrics(array $totals, int $subjectsTotal): array
    {
        return [
            [
                'label' => 'Horas estudadas',
                'value' => $this->formatSecondsAsHours($totals['seconds_total']),
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
            ['label' => 'Materias', 'href' => route('study-subjects.index'), 'icon' => 'book', 'active' => false],
            ['label' => 'Sessoes', 'href' => '#sessoes', 'icon' => 'clock', 'active' => false],
            ['label' => 'Anotacoes', 'href' => '#anotacoes', 'icon' => 'notes', 'active' => false],
            ['label' => 'Metas', 'href' => '#metas', 'icon' => 'target', 'active' => false],
            ['label' => 'Conquistas', 'href' => '#conquistas', 'icon' => 'trophy', 'active' => false],
            ['label' => 'Amigos', 'href' => '#amigos', 'icon' => 'users', 'active' => false],
            ['label' => 'Ranking', 'href' => '#ranking', 'icon' => 'chart', 'active' => false],
            ['label' => 'Configuracoes', 'href' => route('profile.show', $user), 'icon' => 'settings', 'active' => false],
        ];
    }

    private function buildRecentActivity(User $user, $recentSessions, $recentLogs): array
    {
        $items = [];

        if ($recentLogs->isNotEmpty()) {
            $log = $recentLogs->first();
            $items[] = [
                'avatar' => $this->avatarFromName($user->displayName()),
                'title' => 'Voce estudou '.$log->title,
                'detail' => Str::limit($log->content, 48),
                'when' => $log->created_at ? $log->created_at->diffForHumans() : $log->log_date->diffForHumans(),
                'accent' => 'violet',
            ];
        }

        if ($recentSessions->isNotEmpty()) {
            $session = $recentSessions->first();
            $items[] = [
                'avatar' => 'S',
                'title' => 'Sessao criada em '.$session->subject,
                'detail' => $this->formatTimer((int) $session->duration_seconds).' · '.($session->notes ?: 'sem observacoes'),
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

    private function buildCircle(User $user): array
    {
        $friendIds = $user->acceptedFriendIds();
        $circleUserIds = $friendIds->push($user->id)->unique()->values();

        $posts = CirclePost::query()
            ->with(['user', 'replies.user'])
            ->whereIn('user_id', $circleUserIds)
            ->latest()
            ->limit(5)
            ->get();

        $friendSessions = StudySession::query()
            ->with('user')
            ->whereIn('user_id', $friendIds)
            ->latest('started_at')
            ->limit(5)
            ->get();

        $feed = collect()
            ->merge($posts->map(fn ($post) => [
                'type' => 'post',
                'sort_at' => $post->created_at,
                'post' => $post,
            ]))
            ->merge($friendSessions->map(fn ($session) => [
                'type' => 'session',
                'sort_at' => $session->started_at,
                'session' => $session,
            ]))
            ->sortByDesc(fn ($item) => $item['sort_at']?->timestamp ?? 0)
            ->take(8)
            ->values();

        return [
            'friend_ids' => $friendIds,
            'feed' => $feed,
        ];
    }

    private function buildFriendNotifications(User $user): array
    {
        $pendingReceived = Friendship::query()
            ->with('requester')
            ->where('addressee_id', $user->id)
            ->where('status', Friendship::STATUS_PENDING)
            ->latest()
            ->limit(5)
            ->get();

        $acceptedSent = Friendship::query()
            ->with('addressee')
            ->where('requester_id', $user->id)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->latest()
            ->limit(5)
            ->get();

        return [
            'pending_received' => $pendingReceived,
            'accepted_sent' => $acceptedSent,
            'count' => $pendingReceived->count() + $acceptedSent->count(),
        ];
    }

    private function finishedSecondsTodayForCurrentSubject(User $user, $currentSession, string $today): int
    {
        return (int) $user->studySessions()
            ->whereNotNull('ended_at')
            ->whereDate('started_at', $today)
            ->when(
                $currentSession->study_subject_id,
                fn ($query) => $query->where('study_subject_id', $currentSession->study_subject_id),
                fn ($query) => $query->where('subject', $currentSession->subject)
            )
            ->sum('duration_seconds');
    }

    private function buildAchievements($recentLogs): array
    {
        return [
            [
                'icon' => 'fire',
                'title' => max(3, $recentLogs->count()).' dias consecutivos',
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

    private function formatSecondsAsHours(int $seconds): string
    {
        return $this->formatMinutesAsHours(intdiv(max(0, $seconds), 60));
    }

    private function formatTimer(int $seconds): string
    {
        return gmdate('H:i:s', max(0, $seconds));
    }
}
