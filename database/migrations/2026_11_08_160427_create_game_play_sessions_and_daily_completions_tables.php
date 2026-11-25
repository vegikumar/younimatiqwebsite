<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_play_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 64)->unique();
            $table->string('game_key', 80);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('used')->default(false);
            $table->boolean('rewarded')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['game_key', 'user_id']);
        });

        Schema::create('game_daily_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('game_key', 80);
            $table->date('day');
            $table->unsignedInteger('points')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'game_key', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_daily_completions');
        Schema::dropIfExists('game_play_sessions');
    }
};
