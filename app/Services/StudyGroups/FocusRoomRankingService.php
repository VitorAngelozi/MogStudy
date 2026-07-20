<?php

namespace App\Services\StudyGroups;

use App\Models\StudyFocusRoom;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FocusRoomRankingService
{
    public function forRoom(StudyFocusRoom $room, string $period = 'today', int $limit = 5): Collection
    {
        [$start, $end] = $this->rangeFor($period);

        return StudySession::query()
            ->select('user_id', DB::raw('SUM(duration_seconds) as seconds'))
            ->with('user')
            ->where('study_focus_room_id', $room->id)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$start, $end])
            ->groupBy('user_id')
            ->orderByDesc('seconds')
            ->limit($limit)
            ->get();
    }

    private function rangeFor(string $period): array
    {
        return match ($period) {
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [Carbon::today(), Carbon::today()->endOfDay()],
        };
    }
}
