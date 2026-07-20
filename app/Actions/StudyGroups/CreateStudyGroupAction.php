<?php

namespace App\Actions\StudyGroups;

use App\Models\StudyGroup;
use App\Models\StudyGroupMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateStudyGroupAction
{
    public function execute(User $owner, array $data): StudyGroup
    {
        return DB::transaction(function () use ($owner, $data) {
            $group = StudyGroup::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
                'visibility' => $data['visibility'],
                'password_hash' => ($data['visibility'] ?? null) === StudyGroup::VISIBILITY_PASSWORD
                    ? Hash::make($data['password'])
                    : null,
                'status' => StudyGroup::STATUS_ACTIVE,
                'code' => $this->generateCode(),
            ]);

            StudyGroupMember::create([
                'study_group_id' => $group->id,
                'user_id' => $owner->id,
                'role' => StudyGroupMember::ROLE_OWNER,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }

    private function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (StudyGroup::where('code', $code)->exists());

        return $code;
    }
}
