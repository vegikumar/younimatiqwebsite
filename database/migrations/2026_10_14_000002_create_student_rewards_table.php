<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('badge_id')->nullable()->constrained('milestone_badges')->nullOnDelete();
            $table->integer('xp_amount')->default(0);
            $table->json('compliments')->nullable();
            $table->text('level_up_tip')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->timestamp('awarded_at')->useCurrent();
            $table->timestamps();

            $table->index(['student_id', 'awarded_at']);
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_rewards');
    }
};
