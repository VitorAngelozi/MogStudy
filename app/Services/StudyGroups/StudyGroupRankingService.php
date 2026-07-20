<?php

namespace App\Services\StudyGroups;

use App\Models\StudyGroup;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudyGroupRankingService
{
    public function forGroup(StudyGroup $group, string $period = 'today', int $limit = 5): Collection
    {
        [$start, $end] = $this->rangeFor($period);

        return StudySession::query()
            ->select('user_id', DB::raw('SUM(duration_seconds) as seconds'))
            ->with('user')
            ->where('study_group_id', $group->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$start, $end])
            ->groupBy('user_id')
            ->orderByDesc('seconds')
            ->limit($limit)
            ->get();
    }

    protected function rangeFor(string $period): array
    {
        return match ($period) {
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [Carbon::today(), Carbon::today()->endOfDay()],
        };
    }
}
