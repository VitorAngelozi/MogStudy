<?php

namespace App\Policies;

use App\Models\StudyGroup;
use App\Models\StudyGroupMember;
use App\Models\User;

class StudyGroupPolicy
{
    public function view(User $user, StudyGroup $group): bool
    {
        if ($group->visibility === StudyGroup::VISIBILITY_PUBLIC) {
            return true;
        }

        return $this->isMember($user, $group) || $user->isCircleMemberWith($group->owner);
    }

    public function update(User $user, StudyGroup $group): bool
    {
        return $this->memberRole($user, $group) === StudyGroupMember::ROLE_OWNER;
    }

    public function join(User $user, StudyGroup $group): bool
    {
        if (! $group->isActive()) {
            return false;
        }

        if ($this->isMember($user, $group)) {
            return true;
        }

        return match ($group->visibility) {
            StudyGroup::VISIBILITY_PUBLIC, StudyGroup::VISIBILITY_PASSWORD => true,
            StudyGroup::VISIBILITY_FRIENDS => $user->isCircleMemberWith($group->owner),
            default => false,
        };
    }

    public function leave(User $user, StudyGroup $group): bool
    {
        return $this->isMember($user, $group)
            && $this->memberRole($user, $group) !== StudyGroupMember::ROLE_OWNER;
    }

    public function manageFocusRooms(User $user, StudyGroup $group): bool
    {
        return in_array($this->memberRole($user, $group), [
            StudyGroupMember::ROLE_OWNER,
            StudyGroupMember::ROLE_ADMIN,
        ], true);
    }

    public function startFocusStudy(User $user, StudyGroup $group): bool
    {
        return $group->isActive() && $this->isMember($user, $group);
    }

    private function isMember(User $user, StudyGroup $group): bool
    {
        return $group->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    private function memberRole(User $user, StudyGroup $group): ?string
    {
        return $group->members()
            ->where('user_id', $user->id)
            ->value('role');
    }
}
