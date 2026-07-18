<?php

namespace App\Support;

use App\Models\DailyLog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ActivityHeatmap
{
    public function build(int $userId, ?CarbonInterface $endDate = null): array
    {
        $endedAt = Carbon::parse($endDate ?? now())->startOfDay();
        $startedAt = $endedAt->copy()->subDays(364);
        $gridStart = $startedAt->copy()->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $endedAt->copy()->endOfWeek(CarbonInterface::SUNDAY);
        $minutesByDate = $this->minutesByDate($userId, $startedAt, $endedAt);
        $weeks = [];

        for ($weekStart = $gridStart->copy(); $weekStart <= $gridEnd; $weekStart->addWeek()) {
            $days = [];

            for ($date = $weekStart->copy(); $date < $weekStart->copy()->addWeek(); $date->addDay()) {
                $dateKey = $date->toDateString();
                $isEmpty = $date->lt($startedAt) || $date->gt($endedAt);
                $minutes = $isEmpty ? 0 : (int) ($minutesByDate[$dateKey] ?? 0);

                $days[] = [
                    'date' => $dateKey,
                    'minutes' => $minutes,
                    'level' => $this->contributionLevel($minutes),
                    'is_empty' => $isEmpty,
                    'label' => $isEmpty ? '' : $this->dayLabel($date, $minutes),
                ];
            }

            $weeks[] = [
                'date' => $weekStart->toDateString(),
                'days' => $days,
            ];
        }

        return [
            'rows' => ['Seg', '', 'Qua', '', 'Sex', '', 'Dom'],
            'weeks' => $weeks,
            'months' => $this->buildMonthLabels($weeks, $startedAt, $endedAt),
            'total_days' => 365,
            'started_at' => $startedAt->toDateString(),
            'ended_at' => $endedAt->toDateString(),
        ];
    }

    private function minutesByDate(int $userId, CarbonInterface $startedAt, CarbonInterface $endedAt): Collection
    {
        return DailyLog::query()
            ->where('user_id', $userId)
            ->whereBetween('log_date', [$startedAt->toDateString(), $endedAt->toDateString()])
            ->selectRaw('log_date, sum(study_minutes) as minutes')
            ->groupBy('log_date')
            ->pluck('minutes', 'log_date')
            ->mapWithKeys(fn ($minutes, $date) => [
                Carbon::parse($date)->toDateString() => (int) $minutes,
            ]);
    }

    private function buildMonthLabels(array $weeks, CarbonInterface $startedAt, CarbonInterface $endedAt): array
    {
        $labels = [];
        $previousMonth = null;

        foreach ($weeks as $index => $week) {
            $visibleDays = collect($week['days'])
                ->reject(fn ($day) => $day['is_empty'])
                ->map(fn ($day) => Carbon::parse($day['date']));

            $monthDate = $visibleDays->first(fn (Carbon $date) => $date->day === 1)
                ?? ($index === 0 ? $startedAt : null);

            if (! $monthDate) {
                $monthDate = $visibleDays->first();
            }

            $monthKey = $monthDate?->format('Y-m');

            $labels[] = [
                'label' => $monthKey && $monthKey !== $previousMonth ? $this->shortMonthLabel($monthDate) : '',
                'date' => $monthDate?->toDateString() ?? $endedAt->toDateString(),
            ];

            if ($monthKey) {
                $previousMonth = $monthKey;
            }
        }

        return $labels;
    }

    private function dayLabel(CarbonInterface $date, int $minutes): string
    {
        return $date->format('d/m/Y').' - '.$minutes.' min';
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

    private function shortMonthLabel(CarbonInterface $date): string
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
