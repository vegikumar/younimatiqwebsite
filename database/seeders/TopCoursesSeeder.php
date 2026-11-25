<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Category;

class TopCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Match by slug only so re-seeding never creates duplicate display names.
        $roboticsCategory = Category::firstOrCreate(['slug' => 'robotics'], ['name' => 'Robotics']);
        $minecraftCategory = Category::firstOrCreate(['slug' => 'minecraft'], ['name' => 'Minecraft']);
        $robloxCategory = Category::firstOrCreate(['slug' => 'roblox'], ['name' => 'Roblox']);

        // Card 1: AI-Powered Robotics
        $course1 = Course::create([
            'title' => 'AI-Powered Robotics',
            'slug' => 'ai-powered-robotics-new',
            'description' => 'Design, build, and program real robots — where AI meets engineering.',
            'course_type' => 'live',
            'format' => 'group',
            'age_group' => '8-14',
            'price' => 0,
            'status' => 'published',
            'is_active' => true,
            'is_top_course' => true,
            'thumbnail_url' => asset('images/robotics_course_card.png'),
        ]);
        $course1->categories()->sync([$roboticsCategory->id]);

        // Card 2: Minecraft, Reimagined
        $course2 = Course::create([
            'title' => 'Minecraft, Reimagined',
            'slug' => 'minecraft-reimagined-new',
            'description' => 'The game they already love, turned into a classroom for math and English.',
            'course_type' => 'live',
            'format' => 'group',
            'age_group' => '7-13',
            'price' => 0,
            'status' => 'published',
            'is_active' => true,
            'is_top_course' => true,
            'thumbnail_url' => asset('images/minecraft_course_card.png'),
        ]);
        $course2->categories()->sync([$minecraftCategory->id]);

        // Card 3: Roblox Coding + 3D Modeling
        $course3 = Course::create([
            'title' => 'Roblox Coding + 3D Modeling',
            'slug' => 'roblox-coding-3d-modeling-new',
            'description' => 'Script the mechanics, model the worlds — the game they play, rebuilt from the inside out.',
            'course_type' => 'live',
            'format' => 'group',
            'age_group' => '9-14',
            'price' => 0,
            'status' => 'published',
            'is_active' => true,
            'is_top_course' => true,
            'thumbnail_url' => asset('images/roblox_course_card.png'),
        ]);
        $course3->categories()->sync([$robloxCategory->id]);
    }
}
