<?php

namespace Database\Seeders;

use App\Models\FeaturedProject;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class CommunityContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Truncate existing to prevent duplicates during testing
        FeaturedProject::truncate();
        Testimonial::truncate();

        // 1. Seed Featured Projects
        FeaturedProject::create([
            'title' => 'Mars Rover Simulation',
            'author_name' => 'Alex T.',
            'category' => 'Robotics',
            'video_path' => '',
            'project_url' => 'https://scratch.mit.edu',
            'views_count' => rand(100, 500),
            'likes_count' => rand(50, 200),
            'is_active' => true,
        ]);

        FeaturedProject::create([
            'title' => 'Math Galaxy Quest',
            'author_name' => 'Sarah M.',
            'category' => 'Math',
            'video_path' => '',
            'project_url' => 'https://scratch.mit.edu',
            'views_count' => rand(100, 500),
            'likes_count' => rand(50, 200),
            'is_active' => true,
        ]);

        FeaturedProject::create([
            'title' => 'Minecraft Redstone Calculator',
            'author_name' => 'Leo R.',
            'category' => 'Minecraft',
            'video_path' => '',
            'project_url' => '',
            'views_count' => rand(100, 500),
            'likes_count' => rand(50, 200),
            'is_active' => true,
        ]);

        // 2. Seed Testimonials
        Testimonial::create([
            'author_name' => 'Jessica Wong',
            'text' => 'Younimatiq completely transformed how my son looks at math. He went from dreading his homework to building his own math-based games in Scratch. The 1:1 attention from his tutor is incredible.',
            'rating' => 5,
            'avatar_path' => '',
            'video_path' => '',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Testimonial::create([
            'author_name' => 'Marcus Thorne',
            'text' => 'The robotics curriculum is top-notch. My daughter loves the interactive simulator and the tutor is always so patient when explaining complex engineering concepts. Highly recommend!',
            'rating' => 5,
            'avatar_path' => '',
            'video_path' => '',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Testimonial::create([
            'author_name' => 'Elena Rodriguez',
            'text' => 'We tried other online coding classes, but the Minecraft integration here is what finally got him to stick with it. He is learning real Python concepts without even realizing it because he is having so much fun.',
            'rating' => 5,
            'avatar_path' => null,
            'video_path' => null,
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }
}
