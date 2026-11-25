<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create classroom_discussions table
        Schema::create('classroom_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('classroom_discussions')->cascadeOnDelete();
            $table->text('content');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });

        // 2. Create classroom_mentions table
        Schema::create('classroom_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained('classroom_discussions')->cascadeOnDelete();
            $table->foreignId('mentioned_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        // 3. Create direct_conversations table
        Schema::create('direct_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // 4. Create direct_messages table
        Schema::create('direct_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('direct_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_type')->nullable(); // video, document, image
            $table->bigInteger('file_size_bytes')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // 5. Create class_recordings table
        Schema::create('class_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('live_session_id')->nullable()->constrained('live_sessions')->nullOnDelete();
            $table->string('title');
            $table->string('recording_url');
            $table->integer('duration_minutes')->nullable();
            $table->decimal('file_size_mb', 10, 2)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_recordings');
        Schema::dropIfExists('direct_messages');
        Schema::dropIfExists('direct_conversations');
        Schema::dropIfExists('classroom_mentions');
        Schema::dropIfExists('classroom_discussions');
    }
};
