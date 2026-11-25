<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatbotTraining;
use App\Models\Course;
use App\Models\Faq;
use App\Models\Category;

class ChatbotSeeder extends Seeder
{
    public function run()
    {
        ChatbotTraining::truncate();

        // 1. General Info
        $general = [
            [
                'question' => 'Hello',
                'answer' => 'Hello! I am the Younimatiq virtual assistant. I can help you with questions about our courses, prices, and how the platform works. What would you like to know?',
                'tags' => 'hi hello hey hii greeting greetings'
            ],
            [
                'question' => 'How are you?',
                'answer' => 'I am just a bot, but I am doing great! Ready to help you discover our amazing courses.',
                'tags' => 'how are you doing today'
            ],
            [
                'question' => 'What courses do you have?',
                'answer' => 'We offer a wide variety of interactive courses for kids, including Coding, Robotics, Game Design, Arts, and Language. You can view all our courses by clicking on the "Courses" link in the menu!',
                'tags' => 'courses list class classes offer teach provide available'
            ],
            [
                'question' => 'How can I contact you?',
                'answer' => 'You can reach us via email at support@younimatiq.com, call our US line at +1 (202) 794 4843, or our Australia line at +61 470 214 835. You can also WhatsApp us at +1 510 453 7850!',
                'tags' => 'contact email phone number whatsapp reach call support help customer service'
            ],
            [
                'question' => 'What is Younimatiq?',
                'answer' => 'Younimatiq is a premium online learning platform offering live interactive classes in tech, coding, robotics, arts, and more for kids.',
                'tags' => 'about younimatiq what is'
            ],
            [
                'question' => 'How does it work?',
                'answer' => 'Parents enroll their children in courses. Tutors teach live classes online via Zoom. Students learn through interactive projects and get certificates!',
                'tags' => 'how work method process'
            ],
            [
                'question' => 'Who are the tutors?',
                'answer' => 'Our tutors are carefully vetted experts and passionate educators who love teaching kids.',
                'tags' => 'tutors teachers who teach'
            ],
            [
                'question' => 'How much does it cost?',
                'answer' => 'Course prices vary, but many of our live interactive courses are around $149.99 for a full multi-week program. We also offer $1 demo classes!',
                'tags' => 'cost price expensive cheap fee pay'
            ],
            [
                'question' => 'How do I join a class?',
                'answer' => 'Once enrolled, you can log into your student dashboard and find the Zoom link to join your live session at the scheduled time.',
                'tags' => 'join class zoom link attend'
            ]
        ];

        foreach ($general as $g) {
            ChatbotTraining::create($g);
        }

        // 2. FAQs
        $faqs = Faq::all();
        foreach ($faqs as $faq) {
            ChatbotTraining::create([
                'question' => $faq->question,
                'answer' => $faq->answer,
                'tags' => strtolower(preg_replace('/[^a-zA-Z0-9 ]/', '', $faq->question))
            ]);
        }

        // 3. Categories
        $categories = Category::all();
        foreach ($categories as $cat) {
            ChatbotTraining::create([
                'question' => 'Do you have ' . $cat->name . ' classes?',
                'answer' => 'Yes! We offer amazing ' . $cat->name . ' classes. ' . $cat->description . ' You can find them in our course catalog.',
                'tags' => strtolower($cat->name . ' ' . $cat->slug)
            ]);
        }

        // 4. Courses
        $courses = Course::with('category')->get();
        foreach ($courses as $course) {
            // Summary question
            ChatbotTraining::create([
                'question' => 'Tell me about ' . $course->title,
                'answer' => $course->title . ' is a fantastic course! ' . $course->description . ' It is designed for ' . ($course->seo_metadata['age_label'] ?? 'kids') . ' and costs $' . $course->price . '.',
                'tags' => strtolower($course->title . ' ' . ($course->category->name ?? '') . ' ' . $course->age_group)
            ]);
            
            // Age query
            ChatbotTraining::create([
                'question' => 'How old do you have to be for ' . $course->title,
                'answer' => $course->title . ' is specifically designed for ' . ($course->seo_metadata['age_label'] ?? 'all ages') . '.',
                'tags' => strtolower($course->title . ' age old years')
            ]);
            
            // Cost query
            ChatbotTraining::create([
                'question' => 'How much is ' . $course->title,
                'answer' => 'The enrollment fee for ' . $course->title . ' is $' . $course->price . '.',
                'tags' => strtolower($course->title . ' price cost much')
            ]);
        }
    }
}
