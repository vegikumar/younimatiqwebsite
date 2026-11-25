<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommunityDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\FeaturedProject::truncate();
        \App\Models\Testimonial::truncate();

        $dummyVideo = 'https://www.w3schools.com/html/mov_bbb.mp4';
        
        \App\Models\FeaturedProject::insert([
            [
                'title' => 'Space Exploration Game',
                'author_name' => 'Alex J.',
                'category' => 'Coding',
                'video_path' => $dummyVideo,
                'project_url' => '#',
                'views_count' => 1250,
                'likes_count' => 342,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Smart Plant Waterer',
                'author_name' => 'Maya S.',
                'category' => 'Robotics',
                'video_path' => $dummyVideo,
                'project_url' => '#',
                'views_count' => 890,
                'likes_count' => 210,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Digital Art Portfolio',
                'author_name' => 'Leo T.',
                'category' => 'Design',
                'video_path' => $dummyVideo,
                'project_url' => '#',
                'views_count' => 540,
                'likes_count' => 120,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'AI Chatbot Helper',
                'author_name' => 'Sarah W.',
                'category' => 'AI',
                'video_path' => $dummyVideo,
                'project_url' => '#',
                'views_count' => 2100,
                'likes_count' => 550,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        \App\Models\Testimonial::insert([
            [
                'author_name' => 'The Smith Family',
                'text' => 'Younimatiq has completely transformed how our kids learn. They actually look forward to class!',
                'rating' => 5,
                'avatar_path' => null,
                'video_path' => $dummyVideo,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'author_name' => 'Jennifer K.',
                'text' => 'The instructors are incredibly patient and make coding so much fun for my 9-year-old.',
                'rating' => 5,
                'avatar_path' => null,
                'video_path' => $dummyVideo,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'author_name' => 'David M.',
                'text' => 'We tried other programs but nothing engaged my son like the robotics camp here. Highly recommend!',
                'rating' => 5,
                'avatar_path' => null,
                'video_path' => $dummyVideo,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'author_name' => 'Elena R.',
                'text' => 'An absolute game changer for our daughter. Her confidence has soared since she started building her own apps.',
                'rating' => 5,
                'avatar_path' => null,
                'video_path' => $dummyVideo,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
