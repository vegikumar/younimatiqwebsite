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
        Schema::create('class_feedback', function (Blueprint $table) {
            $table->id();
            $table->morphs('bookable'); // bookable_id, bookable_type
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('student_id')->nullable(); // Demo class students might not have user account yet
            $table->string('session_type'); // 'demo' or 'regular'
            $table->string('customer_type')->nullable(); // Admin decided
            $table->json('feedback_data'); // Tutor answers
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_feedback');
    }
};
