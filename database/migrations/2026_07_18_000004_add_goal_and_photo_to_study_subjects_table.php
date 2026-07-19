<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_subjects', function (Blueprint $table) {
            $table->string('goal_period', 16)->nullable()->after('description');
            $table->unsignedInteger('goal_minutes')->nullable()->after('goal_period');
            $table->string('photo_path')->nullable()->after('goal_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('study_subjects', function (Blueprint $table) {
            $table->dropColumn(['goal_period', 'goal_minutes', 'photo_path']);
        });
    }
};
