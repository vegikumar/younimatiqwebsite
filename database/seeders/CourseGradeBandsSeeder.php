<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseGradeBandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mapping = [
            // AI
            'AI & Scratch Fun Camp: Imagine' => [0, 5],
            'AI Coding for Kids – Discover the Magic' => [0, 5],
            'AI Explorers Club: A New AI Adventure' => [3, 5],
            'AI Coding for Kids: Build Smart Apps' => [3, 8],
            'AI Projects Weekly Lab' => [3, 8],
            'Introduction to machine learning' => [3, 8],
            'Weekly AI Inventors Course' => [3, 8],
            'AI-Powered Robotics' => [3, 8],
            'Minecraft AI Inventors' => [3, 8],
            
            // Minecraft
            'Minecraft Coding Odyssey' => [0, 5],
            'Minecraft Redstone Mania' => [0, 5],
            'Minecraft Money Masters' => [0, 5],
            'Minecraft Game Builders' => [3, 5],
            'Minecraft Time Travelers' => [3, 5],
            'Minecraft Science Explorers' => [3, 8],
            'Minecraft Python Quest' => [3, 8],
            'Minecraft Math Fun' => [0, 5],
            'Minecraft Math Quest: Build, Solve & Explore | Grades 3' => [3, 5],
            'Minecraft Math Quest: Build, Solve & Explore |Grade 5' => [3, 8],
            'Minecraft Builder Adventures' => [0, 5],
            'Minecraft Storytellers Course' => [0, 5],
            'Minecraft, Reimagined' => [0, 8],
            
            // Roblox
            'Roblox Math Quest' => [0, 5],
            'Obby Master Course' => [0, 5],
            'Roblox Mini Game Maker' => [3, 8],
            'Roblox Grow a Garden Club' => [3, 5],
            'Roblox Summer Gaming Club' => [3, 8],
            'Roblox Game Design: Build Your Own 3D World with Zero' => [3, 8],
            'Roblox Game Design Course: Build Your Own 3D' => [3, 8],
            'Roblox Animation' => [3, 8],
            'Roblox Lua Coding' => [6, 12],
            'Roblox Coding + 3D Modeling' => [6, 12],
            'Roblox 3D Modeling +Coding' => [6, 12],
            
            // Coding
            'Scratch Coding for Beginners' => [0, 5],
            'Scratch Summer Camp: Build Games, Stories' => [0, 5],
            'Scratch Summer Camp: Build Racing' => [3, 5],
            'Python for Kids' => [6, 12],
            'Computer & Digital Literacy' => [3, 8],
            
            // Robotics & STEM
            'STEAM & Robotics Course: Code' => [3, 8],
            'Arduino Robotics & STEM Lab' => [3, 8],
            'Robotics & Automation With Python' => [6, 12],
            '3D Design: Bring Ideas' => [3, 8],
            'Build Real Robots with Tinkercad' => [3, 8],
            
            // Math
            'Grade 4 Math' => [3, 5],
            'Grade 5 Math' => [3, 5],
            'Grade 6 Math' => [6, 8],
            'Grade 7 Math' => [6, 8],
            'Grade 8 Math' => [6, 8],
            'Grade 9 Math' => [9, 12],

            // Default fallback for any remaining ones (approx)
            'Little Gita' => [0, 5],
            'Mindful Warrior' => [0, 5],
            'Moral Tales' => [0, 5],
            'Sanatan Explorers' => [0, 5],
            'Story & Grammar' => [0, 5],
            'Arts and Animations' => [0, 5],
            'Around the World Art' => [0, 5],
            'Design Like an Artist' => [0, 5],
            'Summer Nature Artist' => [0, 5],
            'Creative Mixed Media' => [0, 5],
            'Young Artists Drawing' => [0, 5],
            'Mythical Creatures Drawing' => [0, 5],
            'CHESS ADVENTURES' => [0, 5],
            'Think Like a Computer' => [0, 2],
            'Computational Thinking Explorers' => [3, 3],
            'Computational Thinking Builders' => [4, 4],
            'Computational Thinking Innovators' => [5, 5],
            'Minecraft AI Python' => [3, 8],
            'Beginner AI Workshop' => [0, 5]
        ];

        $courses = \App\Models\Course::all();
        foreach ($courses as $course) {
            foreach ($mapping as $key => $grades) {
                if (stripos($course->title, $key) !== false) {
                    $course->grade_min = $grades[0];
                    $course->grade_max = $grades[1];
                    $course->save();
                    break;
                }
            }
        }
    }
}
