<?php

namespace App\Policies;

use App\Models\StudyFocusRoom;
use App\Models\StudyGroupMember;
use App\Models\User;

class StudyFocusRoomPolicy
{
    public function view(User $user, StudyFocusRoom $focusRoom): bool
    {
        return $focusRoom->group->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function update(User $user, StudyFocusRoom $focusRoom): bool
    {
        return $this->canManage($user, $focusRoom);
    }

    public function delete(User $user, StudyFocusRoom $focusRoom): bool
    {
        return $this->canManage($user, $focusRoom);
    }

    public function start(User $user, StudyFocusRoom $focusRoom): bool
    {
        return $focusRoom->is_active
            && $focusRoom->group->isActive()
            && $focusRoom->group->members()->where('user_id', $user->id)->exists();
    }

    private function canManage(User $user, StudyFocusRoom $focusRoom): bool
    {
        return $focusRoom->group->members()
            ->where('user_id', $user->id)
            ->whereIn('role', [StudyGroupMember::ROLE_OWNER, StudyGroupMember::ROLE_ADMIN])
            ->exists();
    }
}
