<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourseReviewSeeder extends Seeder
{
    /** @var list<array{name: string, email: string, comments: list<string>}> */
    private array $reviewers = [
        [
            'name' => 'Jennifer Carter',
            'email' => 'parent.jennifer@example.com',
            'comments' => [
                'My child looks forward to every class. The projects are fun and the tutor is wonderfully patient.',
                'Clear lessons and great live feedback. We saw real progress within two weeks.',
                'Exactly the right mix of challenge and encouragement for this age.',
            ],
        ],
        [
            'name' => 'Charlotte Bennett',
            'email' => 'parent.charlotte@example.com',
            'comments' => [
                'Engaging from the first session. Confidence has gone up at home and in school.',
                'Well-structured curriculum with creative projects kids are proud to share.',
                'Booking the demo was easy and the full course has been worth every minute.',
            ],
        ],
        [
            'name' => 'Jack Sullivan',
            'email' => 'parent.jack@example.com',
            'comments' => [
                'Hands-on learning that never feels like homework. Highly recommend.',
                'The instructor explains concepts clearly and keeps the energy high.',
                'Great for building real skills while still feeling playful.',
            ],
        ],
        [
            'name' => 'Wei Ling Ong',
            'email' => 'parent.weiling@example.com',
            'comments' => [
                'Fantastic live format. Our child finished a project they still talk about.',
                'Supportive environment and solid learning outcomes.',
                'Perfect balance between creativity and foundational skills.',
            ],
        ],
        [
            'name' => 'Michael Reyes',
            'email' => 'parent.michael@example.com',
            'comments' => [
                'Pace is age-appropriate and the materials are excellent.',
                'We love the certificate at the end — it motivates our kid to finish strong.',
                'Professional tutoring with a warm, welcoming class vibe.',
            ],
        ],
        [
            'name' => 'Chloe Marsh',
            'email' => 'parent.chloe@example.com',
            'comments' => [
                'Booked a $1 demo first and enrolled the same week. So glad we did.',
                'Interactive classes and thoughtful feedback after every project.',
                'One of the best online programs we have tried.',
            ],
        ],
    ];

    public function run(): void
    {
        $users = [];
        foreach ($this->reviewers as $reviewer) {
            $users[] = User::firstOrCreate(
                ['email' => $reviewer['email']],
                [
                    'name' => $reviewer['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'status' => 'active',
                ]
            );
        }

        $courses = Course::where('status', 'published')->get();
        if ($courses->isEmpty()) {
            return;
        }

        $count = 0;
        foreach ($courses as $courseIndex => $course) {
            // 3–4 reviews per course, rotated across reviewers
            $reviewCount = 3 + ($courseIndex % 2);
            for ($i = 0; $i < $reviewCount; $i++) {
                $reviewerMeta = $this->reviewers[($courseIndex + $i) % count($this->reviewers)];
                $user = $users[($courseIndex + $i) % count($users)];
                $rating = [5, 5, 5, 4, 5][($courseIndex + $i) % 5];
                $comment = $reviewerMeta['comments'][$i % count($reviewerMeta['comments'])];

                Review::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'rating' => $rating,
                        'comment' => $comment.' ('.$course->title.')',
                    ]
                );
                $count++;
            }
        }

        $this->command?->info("Seeded {$count} course reviews across {$courses->count()} courses.");
    }
}
