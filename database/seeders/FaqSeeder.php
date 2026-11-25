<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            // General / Welcome FAQs
            [
                'question' => 'How often are classes, and how long do they run?',
                'answer' => 'Most students attend 2–3 sessions per week, 45–55 minutes each. Schedules flex around school, sports, and family commitments.',
                'category' => 'General',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => 'What devices does my child need?',
                'answer' => 'A laptop or tablet with a webcam and stable internet connection. Coding and AI Mastery tracks work best on a laptop.',
                'category' => 'General',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => 'Is the curriculum aligned with my child\'s school?',
                'answer' => 'Yes — coaches customize sessions to complement your child\'s grade-level curriculum, aligned to Common Core standards.',
                'category' => 'General',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => 'Can my child try a class before enrolling?',
                'answer' => 'Absolutely — every family starts with one free trial class, no commitment required.',
                'category' => 'Enrollment',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'question' => 'Can my child combine two subjects, like Coding and AI Mastery?',
                'answer' => 'Yes, many families mix and match tracks. We\'ll help you build a schedule that fits.',
                'category' => 'Curriculum',
                'sort_order' => 5,
                'is_active' => true,
            ],
            
            // Pricing FAQs
            [
                'question' => 'Can I reschedule a class?',
                'answer' => 'Yes! We understand that schedules can change. You can easily reschedule any class with at least 24 hours\' notice without losing your credit.',
                'category' => 'Pricing',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'question' => 'Are there any hidden fees?',
                'answer' => 'Absolutely not. The price you see is the price you pay. All course materials and platform access are included in your plan.',
                'category' => 'Pricing',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'question' => 'What happens if we miss a class without prior notice?',
                'answer' => 'If you miss a class without prior notice, we provide a recorded session if applicable, and our support team will help you catch up on the curriculum.',
                'category' => 'Pricing',
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'question' => 'Do you offer refunds?',
                'answer' => 'We offer a 7-day money-back guarantee for unused classes if you are not satisfied with your experience after the first session.',
                'category' => 'Pricing',
                'sort_order' => 9,
                'is_active' => true,
            ],
            
            // Landing Pages FAQs
            [
                'question' => 'Why 1:1 instead of group classes?',
                'answer' => 'Every Younimatiq class is one-on-one with a dedicated instructor, so the pace, project difficulty, and feedback are built entirely around your child — no waiting on slower classmates and no feeling rushed by faster ones.',
                'category' => 'General',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'question' => 'Do I need to buy hardware separately?',
                'answer' => 'Explorer and Builder tracks use simulators for most lessons, so no hardware purchase is required to start. The Innovator track uses physical kits, available to purchase or rent separately.',
                'category' => 'General',
                'sort_order' => 11,
                'is_active' => true,
            ],
            [
                'question' => 'Can I switch tracks later?',
                'answer' => 'Yes — you can move up (or down) a track as your child progresses, at the start of your next 24-Class Plan. Your instructor and personalized pacing stay the same.',
                'category' => 'Curriculum',
                'sort_order' => 12,
                'is_active' => true,
            ],
            [
                'question' => 'What happens if we miss a class?',
                'answer' => 'If you miss a class, a recording of the session will be sent to you so your child doesn\'t fall behind. If anything in it isn\'t clear, one of our Master Teachers will step in to walk your child through it.',
                'category' => 'General',
                'sort_order' => 13,
                'is_active' => true,
            ]
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
