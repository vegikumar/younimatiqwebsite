<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable()->index(); // For 1:1
            $table->unsignedBigInteger('live_session_id')->nullable()->index(); // For 1:M
            $table->unsignedBigInteger('tutor_id')->index();
            $table->unsignedBigInteger('student_id')->nullable()->index(); // Filled for 1:1, null for 1:M if handled via enrollment
            $table->dateTime('scheduled_at')->index();
            $table->string('zoom_join_url')->nullable();
            $table->string('zoom_start_url')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
            
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
