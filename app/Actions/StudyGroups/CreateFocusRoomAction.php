<?php

namespace App\Actions\StudyGroups;

use App\Models\StudyFocusRoom;
use App\Models\StudyGroup;
use Illuminate\Support\Facades\DB;

class CreateFocusRoomAction
{
    public function execute(StudyGroup $group, array $data): StudyFocusRoom
    {
        return DB::transaction(function () use ($group, $data) {
            $nextPosition = ((int) $group->focusRooms()->max('position')) + 1;

            return StudyFocusRoom::create([
                'study_group_id' => $group->id,
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
                'icon' => $data['icon'] ?: 'book',
                'position' => $nextPosition,
                'is_active' => true,
            ]);
        });
    }
}
