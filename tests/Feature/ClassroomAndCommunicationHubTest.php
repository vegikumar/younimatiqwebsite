<?php

namespace Tests\Feature;

use App\Models\ClassroomDiscussion;
use App\Models\ClassroomMention;
use App\Models\ClassRecording;
use App\Models\Course;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EmailService;
use App\Services\MentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassroomAndCommunicationHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_discussion_with_mention_triggers_email_notification(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'name' => 'Professor Smith']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Alice Johnson', 'email' => 'alice@example.com']);
        
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Advanced Coding',
            'description' => 'Course description',
            'price' => 50,
            'course_type' => 'live',
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        // Post discussion with @Alice Johnson
        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.classroom.discussion.store', $course), [
            'content' => 'Great work today @"Alice Johnson"! Please review unit 2.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classroom_discussions', [
            'course_id' => $course->id,
            'user_id' => $tutor->id,
        ]);

        $discussion = ClassroomDiscussion::first();

        // Verify mention record created
        $this->assertDatabaseHas('classroom_mentions', [
            'discussion_id' => $discussion->id,
            'mentioned_user_id' => $student->id,
        ]);
    }

    public function test_direct_messaging_with_attachment_size_limits(): void
    {
        Storage::fake('public');

        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        
        $conversation = DirectConversation::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'last_message_at' => now(),
        ]);

        // Send normal message with document attachment under 10MB
        $file = UploadedFile::fake()->create('homework.pdf', 500); // 500 KB

        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.messages.store', $conversation), [
            'message' => 'Here is your homework file.',
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('direct_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $tutor->id,
            'attachment_name' => 'homework.pdf',
        ]);
    }

    public function test_post_class_recordings_management(): void
    {
        $tutor = User::factory()->create(['role' => 'tutor']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Math Section A',
            'description' => 'Math',
            'price' => 30,
            'course_type' => 'live',
        ]);

        $response = $this->actingAs($tutor, 'tutor')->post(route('tutor.classroom.recording.store', $course), [
            'title' => 'Session 1 Replay',
            'recording_url' => 'https://youtube.com/watch?v=replay123',
            'duration_minutes' => 45,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('class_recordings', [
            'course_id' => $course->id,
            'title' => 'Session 1 Replay',
            'recording_url' => 'https://youtube.com/watch?v=replay123',
        ]);

        $recording = ClassRecording::first();

        $deleteResponse = $this->actingAs($tutor, 'tutor')->delete(route('tutor.classroom.recording.destroy', $recording));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('class_recordings', ['id' => $recording->id]);
    }
}
