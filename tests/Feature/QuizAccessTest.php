<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_submit_quiz_without_enrollment(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Quiz Course',
            'description' => 'Desc',
            'price' => 10,
            'course_type' => 'self-paced',
            'status' => 'published',
        ]);
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Quiz 1',
            'questions' => [['question' => '2+2?', 'options' => ['3', '4'], 'correct' => '4']],
            'passing_score' => 70,
        ]);

        $response = $this->actingAs($student, 'web')
            ->post(route('student.quizzes.submit', $quiz), ['answers' => ['4']]);

        $response->assertForbidden();
    }

    public function test_enrolled_student_can_submit_quiz(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active']);
        $course = Course::create([
            'tutor_id' => $tutor->id,
            'title' => 'Quiz Course 2',
            'description' => 'Desc',
            'price' => 10,
            'course_type' => 'self-paced',
            'status' => 'published',
        ]);
        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Quiz 2',
            'questions' => [[
                'id' => 1,
                'question' => '2+2?',
                'type' => 'mcq',
                'options' => ['3', '4'],
                'correctOption' => '4',
            ]],
            'passing_score' => 70,
        ]);
        QuizAssignment::create([
            'quiz_id' => $quiz->id,
            'assignable_type' => Course::class,
            'assignable_id' => $course->id,
        ]);

        $response = $this->actingAs($student, 'web')
            ->post(route('student.quizzes.submit', $quiz), ['answers' => [1 => '4']]);

        $response->assertRedirect(route('student.quizzes.results', $quiz));
        $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id, 'user_id' => $student->id, 'score' => 100]);
    }
}
