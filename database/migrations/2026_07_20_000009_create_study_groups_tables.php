<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 12)->unique();
            $table->string('description', 500)->nullable();
            $table->string('visibility', 20)->default('public');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index('visibility');
        });

        Schema::create('study_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->dateTime('joined_at');
            $table->timestamps();

            $table->unique(['study_group_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        Schema::create('study_focus_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description', 300)->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['study_group_id', 'name']);
            $table->index(['study_group_id', 'position']);
            $table->index('is_active');
        });

        Schema::create('study_focus_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_focus_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_subject_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['study_focus_room_id', 'status']);
            $table->index(['study_subject_id', 'started_at']);
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->foreignId('study_group_id')->nullable()->after('study_subject_id')->constrained()->nullOnDelete();
            $table->foreignId('study_focus_room_id')->nullable()->after('study_group_id')->constrained()->nullOnDelete();
            $table->string('source_type', 40)->nullable()->after('study_focus_room_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->index(['study_group_id', 'started_at']);
            $table->index(['study_focus_room_id', 'started_at']);
            $table->index(['source_type', 'source_id']);
        });

        $this->importLegacyStudyRooms();
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropIndex(['study_group_id', 'started_at']);
            $table->dropIndex(['study_focus_room_id', 'started_at']);
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropConstrainedForeignId('study_focus_room_id');
            $table->dropConstrainedForeignId('study_group_id');
            $table->dropColumn(['source_type', 'source_id']);
        });

        Schema::dropIfExists('study_focus_participations');
        Schema::dropIfExists('study_focus_rooms');
        Schema::dropIfExists('study_group_members');
        Schema::dropIfExists('study_groups');
    }

    private function importLegacyStudyRooms(): void
    {
        if (! Schema::hasTable('study_rooms')) {
            return;
        }

        $now = now();

        DB::table('study_rooms')
            ->orderBy('id')
            ->each(function ($legacyRoom) use ($now): void {
                $groupId = DB::table('study_groups')->insertGetId([
                    'owner_id' => $legacyRoom->owner_id,
                    'name' => $legacyRoom->name,
                    'code' => $legacyRoom->code,
                    'description' => 'Importado das antigas sessoes em grupo.',
                    'visibility' => in_array($legacyRoom->visibility, ['public', 'friends'], true) ? $legacyRoom->visibility : 'public',
                    'status' => $legacyRoom->ended_at ? 'archived' : 'active',
                    'created_at' => $legacyRoom->created_at ?? $now,
                    'updated_at' => $legacyRoom->updated_at ?? $now,
                ]);

                DB::table('study_group_members')->insertOrIgnore([
                    'study_group_id' => $groupId,
                    'user_id' => $legacyRoom->owner_id,
                    'role' => 'owner',
                    'joined_at' => $legacyRoom->started_at ?? $now,
                    'created_at' => $legacyRoom->created_at ?? $now,
                    'updated_at' => $legacyRoom->updated_at ?? $now,
                ]);

                $roomId = DB::table('study_focus_rooms')->insertGetId([
                    'study_group_id' => $groupId,
                    'name' => $legacyRoom->subject ?: 'Foco geral',
                    'description' => 'Sala importada da sessao antiga.',
                    'icon' => 'book',
                    'position' => 1,
                    'is_active' => ! (bool) $legacyRoom->ended_at,
                    'created_at' => $legacyRoom->created_at ?? $now,
                    'updated_at' => $legacyRoom->updated_at ?? $now,
                ]);

                DB::table('study_room_participants')
                    ->where('study_room_id', $legacyRoom->id)
                    ->orderBy('id')
                    ->each(function ($participant) use ($groupId, $legacyRoom, $now): void {
                        DB::table('study_group_members')->insertOrIgnore([
                            'study_group_id' => $groupId,
                            'user_id' => $participant->user_id,
                            'role' => $participant->user_id === $legacyRoom->owner_id ? 'owner' : 'member',
                            'joined_at' => $participant->joined_at ?? $now,
                            'created_at' => $participant->created_at ?? $now,
                            'updated_at' => $participant->updated_at ?? $now,
                        ]);
                    });
            });
    }
};
