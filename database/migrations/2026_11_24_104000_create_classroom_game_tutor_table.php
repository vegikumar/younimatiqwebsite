<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classroom_game_tutor')) {
            return;
        }

        Schema::create('classroom_game_tutor', function (Blueprint $table) {
            $table->id();
            $table->string('game_key');
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['game_key', 'tutor_id']);
            $table->index('game_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_game_tutor');
    }
};
