<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('addressee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->timestamps();

            $table->unique(['requester_id', 'addressee_id']);
            $table->index(['addressee_id', 'status']);
        });

        Schema::create('circle_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            $table->string('body', 200);
            $table->timestamps();
        });

        Schema::create('circle_post_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circle_post_replies');
        Schema::dropIfExists('circle_posts');
        Schema::dropIfExists('friendships');
    }
};
