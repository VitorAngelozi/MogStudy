<?php

namespace App\Services\StudyGroups;

use App\Models\StudyFocusParticipation;
use App\Models\StudyFocusRoom;
use App\Models\StudyGroup;
use App\Models\StudySession;
use Illuminate\Support\Collection;

class StudyGroupStatisticsService
{
    public function groupSummary(StudyGroup $group): array
    {
        return [
            'members_count' => $group->members()->count(),
            'active_count' => $this->activeParticipationsForGroup($group)->count(),
            'seconds_today' => $this->secondsTodayForGroup($group),
        ];
    }

    public function roomSummary(StudyFocusRoom $room): array
    {
        return [
            'active_count' => $room->activeParticipations()->count(),
            'seconds_today' => $this->secondsTodayForRoom($room),
        ];
    }

    public function activeParticipationsForGroup(StudyGroup $group): Collection
    {
        return StudyFocusParticipation::query()
            ->with(['user', 'studySubject', 'focusRoom'])
            ->where('status', StudyFocusParticipation::STATUS_ACTIVE)
            ->whereHas('focusRoom', fn ($query) => $query->where('study_group_id', $group->id))
            ->latest('started_at')
            ->get();
    }

    public function secondsTodayForGroup(StudyGroup $group): int
    {
        return $this->secondsForQuery(
            StudySession::query()->where('study_group_id', $group->id)
        );
    }

    public function secondsTodayForRoom(StudyFocusRoom $room): int
    {
        return $this->secondsForQuery(
            StudySession::query()->where('study_focus_room_id', $room->id)
        );
    }

    private function secondsForQuery($query): int
    {
        $today = now()->toDateString();
        $finished = (int) (clone $query)
            ->whereNotNull('ended_at')
            ->whereDate('started_at', $today)
            ->sum('duration_seconds');

        $active = (clone $query)
            ->whereNull('ended_at')
            ->whereDate('started_at', $today)
            ->get()
            ->sum(fn (StudySession $session) => $session->effectiveElapsedSeconds());

        return $finished + (int) $active;
    }
}
