<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('study_subjects')
            ->where('goal_period', 'daily')
            ->whereNotNull('goal_minutes')
            ->update([
                'goal_minutes' => DB::raw('goal_minutes * 7'),
                'goal_period' => 'weekly',
            ]);

        DB::table('study_subjects')
            ->whereNotNull('goal_minutes')
            ->whereNull('goal_period')
            ->update([
                'goal_period' => 'weekly',
            ]);
    }

    public function down(): void
    {
        DB::table('study_subjects')
            ->where('goal_period', 'weekly')
            ->whereNotNull('goal_minutes')
            ->update([
                'goal_period' => 'daily',
            ]);
    }
};
