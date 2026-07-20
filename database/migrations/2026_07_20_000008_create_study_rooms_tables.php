<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('subject', 120);
            $table->string('visibility', 20)->default('public');
            $table->string('code', 12)->unique();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'ended_at']);
            $table->index('visibility');
        });

        Schema::create('study_room_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamps();

            $table->index(['study_room_id', 'left_at']);
            $table->index(['user_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_room_participants');
        Schema::dropIfExists('study_rooms');
    }
};
