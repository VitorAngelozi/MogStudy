<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudySubjectCards
{
    public function build(Collection $studySubjects): array
    {
        $maxSeconds = max(1, (int) $studySubjects->max('duration_seconds_total'));
        $tones = ['violet', 'cyan', 'amber', 'emerald', 'indigo'];
        $icons = ['book', 'code', 'database', 'language', 'target'];

        return $studySubjects
            ->values()
            ->map(function ($subject, int $index) use ($maxSeconds, $tones, $icons) {
                $seconds = (int) ($subject->duration_seconds_total ?? 0);

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'description' => $subject->description,
                    'photo_url' => $subject->photo_path ? asset('storage/'.$subject->photo_path) : null,
                    'seconds' => $seconds,
                    'hours_label' => $this->formatStudySecondsAsHours($seconds),
                    'goal_period' => $subject->goal_period,
                    'goal_minutes' => $subject->goal_minutes,
                    'goal_value' => $subject->goal_minutes ? $this->formatGoalValue((int) $subject->goal_minutes) : '',
                    'goal_unit' => $subject->goal_minutes && $subject->goal_minutes % 60 === 0 ? 'hours' : 'minutes',
                    'goal_progress' => $this->buildSubjectGoalProgress($subject, $seconds, $maxSeconds),
                    'goal_label' => $this->buildSubjectGoalLabel($subject),
                    'recent_at' => $this->recentActivityDate($subject)?->toDateTimeString(),
                    'tone' => $tones[$index % count($tones)],
                    'icon' => $icons[$index % count($icons)],
                ];
            })
            ->all();
    }

    public function sortByRecentActivity(Collection $studySubjects): Collection
    {
        return $studySubjects
            ->sortByDesc(fn ($subject) => $this->recentActivityDate($subject)?->timestamp ?? 0)
            ->values();
    }

    private function recentActivityDate($subject): ?Carbon
    {
        $dates = collect([
            $subject->updated_at,
            $subject->created_at,
            $subject->last_studied_at,
        ])->filter();

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates
            ->map(fn ($date) => Carbon::parse($date))
            ->sortByDesc(fn (Carbon $date) => $date->timestamp)
            ->first();
    }

    private function buildSubjectGoalProgress($subject, int $totalSeconds, int $maxSeconds): int
    {
        if (! $subject->goal_minutes) {
            return $totalSeconds > 0 ? (int) round(($totalSeconds / $maxSeconds) * 100) : 0;
        }

        $periodSeconds = $subject->goal_period === 'weekly'
            ? (int) ($subject->duration_seconds_week ?? 0)
            : (int) ($subject->duration_seconds_today ?? 0);

        return min(100, (int) round((intdiv($periodSeconds, 60) / max(1, (int) $subject->goal_minutes)) * 100));
    }

    private function buildSubjectGoalLabel($subject): string
    {
        if (! $subject->goal_minutes) {
            return 'Sem meta definida';
        }

        $period = $subject->goal_period === 'weekly' ? 'semana' : 'dia';

        return 'Meta: '.$this->formatMinutesAsHours((int) $subject->goal_minutes).' por '.$period;
    }

    private function formatGoalValue(int $minutes): string
    {
        if ($minutes % 60 === 0) {
            return (string) intdiv($minutes, 60);
        }

        return (string) $minutes;
    }

    private function formatStudySecondsAsHours(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);

        if ($seconds > 0 && $minutes === 0) {
            $minutes = 1;
        }

        return $this->formatMinutesAsHours($minutes).' estudadas';
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
}
