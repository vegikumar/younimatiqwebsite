<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_game_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_key');
            $table->unsignedInteger('points');
            $table->timestamps();
            $table->unique(['user_id', 'game_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_game_rewards');
    }
};
