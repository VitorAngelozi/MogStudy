<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_groups', function (Blueprint $table) {
            $table->string('password_hash')->nullable()->after('visibility');
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dateTime('paused_at')->nullable()->after('ended_at');
            $table->unsignedInteger('paused_seconds')->default(0)->after('paused_at');
        });

        Schema::table('study_focus_participations', function (Blueprint $table) {
            $table->dateTime('paused_at')->nullable()->after('ended_at');
            $table->unsignedInteger('paused_seconds')->default(0)->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('study_focus_participations', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'paused_seconds']);
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'paused_seconds']);
        });

        Schema::table('study_groups', function (Blueprint $table) {
            $table->dropColumn('password_hash');
        });
    }
};
