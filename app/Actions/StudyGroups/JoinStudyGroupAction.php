<?php

namespace App\Actions\StudyGroups;

use App\Models\StudyGroup;
use App\Models\StudyGroupMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinStudyGroupAction
{
    public function execute(StudyGroup $group, User $user): StudyGroupMember
    {
        return DB::transaction(function () use ($group, $user) {
            return StudyGroupMember::firstOrCreate(
                [
                    'study_group_id' => $group->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => StudyGroupMember::ROLE_MEMBER,
                    'joined_at' => now(),
                ],
            );
        });
    }
}
