<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PlatformDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'STEM', 'slug' => 'stem', 'icon' => 'science', 'sort_order' => 1],
            ['name' => 'Coding', 'slug' => 'coding', 'icon' => 'code', 'sort_order' => 2],
            ['name' => 'AI & Robotics', 'slug' => 'ai-robotics', 'icon' => 'smart_toy', 'sort_order' => 3],
            ['name' => 'Academics', 'slug' => 'academics', 'icon' => 'school', 'sort_order' => 4],
            ['name' => 'Summer Camp', 'slug' => 'summer-camp', 'icon' => 'wb_sunny', 'sort_order' => 5],
            ['name' => 'AI Camp', 'slug' => 'ai-camp', 'icon' => 'psychology', 'sort_order' => 6],
            ['name' => 'Arts & Animation Camp', 'slug' => 'arts-animation-camp', 'icon' => 'palette', 'sort_order' => 7],
            ['name' => 'Minecraft Camp', 'slug' => 'minecraft-camp', 'icon' => 'sports_esports', 'sort_order' => 8],
            ['name' => 'Roblox Camp', 'slug' => 'roblox-camp', 'icon' => 'videogame_asset', 'sort_order' => 9],
            ['name' => 'Robotics Camp', 'slug' => 'robotics-camp', 'icon' => 'precision_manufacturing', 'sort_order' => 10],
            ['name' => 'Lifeskills', 'slug' => 'lifeskills', 'icon' => 'self_improvement', 'sort_order' => 11],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $pages = [
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'content' => '<h2>Privacy Policy</h2><p>Younimatiq respects your privacy. We collect only information necessary to provide educational services to students and parents.</p>'],
            ['title' => 'Terms of Service', 'slug' => 'terms-of-service', 'content' => '<h2>Terms of Service</h2><p>By using Younimatiq, you agree to our terms for online learning services.</p>'],
            ['title' => 'Cookie Policy', 'slug' => 'cookie-policy', 'content' => '<h2>Cookie Policy</h2><p>We use cookies to improve your experience and analyze site traffic.</p>'],
            ['title' => 'Safety Rules', 'slug' => 'safety-rules', 'content' => '<h2>Safety Rules</h2><p>Younimatiq maintains strict safety protocols for all online classes involving children.</p>'],
            ['title' => 'Success Stories', 'slug' => 'success-stories', 'content' => '<h2>Success Stories</h2><p>Parents worldwide trust Younimatiq to prepare their children for the future.</p>'],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(['slug' => $page['slug']], $page);
        }

        Faq::where('question', 'How does the $1 trial work?')->delete();
        Faq::where('question', 'What ages do you teach?')->delete();

        $faqs = [
            [
                'question' => 'What is Younimatiq?',
                'answer' => 'Younimatiq is a live, online learning platform for kids aged 5–16, offering interactive classes in Coding, AI, Minecraft, Roblox, Robotics, Math, English, Music, Arts, Public Speaking, and Life Skills/Sanatan Dharma, Yoga courses — taught by real instructors, in real time, not pre-recorded videos.',
                'category' => 'General',
            ],
            [
                'question' => 'How is Younimatiq different from other EdTech platforms?',
                'answer' => 'Younimatiq keeps classes small and instructor-led, with a game-based, project-first teaching approach — kids build, code, and create rather than passively watch. We\'re also the only EdTech platform where <strong>learning directly contributes to a greener planet</strong> — through our Green Initiative, every 200 classes taught plants a tree, and students earn a Green Energy Badge after completing 24 classes.',
                'category' => 'General',
            ],
            [
                'question' => 'Are Younimatiq classes live or pre-recorded?',
                'answer' => 'All core classes are <strong>100% live</strong>, with real-time interaction between the instructor and student. Recordings may be provided for revision, but the primary learning experience is live.',
                'category' => 'Classes',
            ],
            [
                'question' => 'Who are Younimatiq\'s instructors?',
                'answer' => 'Instructors are vetted subject specialists — background-checked, trained in child-safe teaching practices, and specialized in specific verticals (e.g., a dedicated Robotics teacher, a dedicated Public Speaking coach), rather than generalist tutors covering every subject.',
                'category' => 'Classes',
            ],
            [
                'question' => 'Is my child\'s safety and privacy protected during live classes?',
                'answer' => 'Yes. Classes are conducted on <strong>Zoom</strong>, a secure and monitored platform, sessions can be recorded for quality/safety review, and instructors follow strict child-interaction guidelines. Parents can request session recordings if needed.',
                'category' => 'Safety',
            ],
            [
                'question' => 'What age group is Younimatiq designed for?',
                'answer' => 'Younimatiq serves children aged 5–16, with age-banded course levels (e.g., 5–7, 7–10, 10–14) to ensure content, pace, and teaching style match developmental stage.',
                'category' => 'General',
            ],
            [
                'question' => 'How much does a course cost?',
                'answer' => 'Pricing varies by course, format (1:1 vs. group). Check out our Courses to know more.',
                'category' => 'Enrollment',
            ],
            [
                'question' => 'Can we try a class before enrolling?',
                'answer' => 'Yes — Younimatiq offers a <strong>free trial/demo class</strong> so parents and kids can experience the teaching style, instructor, and platform before committing.',
                'category' => 'Enrollment',
            ],
            [
                'question' => 'What if my child doesn\'t like the course after enrolling?',
                'answer' => 'This is where the <strong>Younimatiq Learning Guarantee</strong> applies.',
                'category' => 'Enrollment',
            ],
            [
                'question' => 'Do you offer refunds or rescheduling?',
                'answer' => 'Yes — missed classes can be rescheduled within policy guidelines, and Younimatiq Credits can be used toward future classes in place of a cash refund in most cases.',
                'category' => 'Enrollment',
            ],
            [
                'question' => 'What do I need for my child to join a class?',
                'answer' => 'A stable internet connection, a laptop/tablet with camera and mic, and for select courses (Robotics, Minecraft, Roblox) any required free software/accounts, which Younimatiq teachers will guide you to set up before the first class.',
                'category' => 'Classes',
            ],
            [
                'question' => 'How is progress tracked?',
                'answer' => 'Parents receive progress updates, certificates on course completion, and — through Younimatiq\'s Green Initiative — visibility into their child\'s Green Energy Badge milestones alongside academic progress.',
                'category' => 'General',
            ],
            [
                'question' => 'What is the Younimatiq Learning Guarantee?',
                'answer' => 'We stand behind the quality of every class. If your child completes a course and you don\'t see meaningful engagement or progress, Younimatiq will offer a <strong>free class credit, a teacher switch, or a tailored learning plan</strong> — at no extra cost — until you\'re confident your child is genuinely learning and growing.',
                'category' => 'General',
            ],
            [
                'question' => 'Does Younimatiq comply with COPPA (Children\'s Online Privacy Protection Act)?',
                'answer' => '<p>Yes. Since Younimatiq serves children under 13 (particularly in the US market), we follow COPPA guidelines, including:</p><ul class="list-disc pl-5 space-y-2 mt-2"><li><strong>Verifiable parental consent</strong> before collecting any personal information from children under 13</li><li><strong>Minimal data collection</strong> — only information necessary to deliver the class experience (name, age/grade, parent contact details)</li><li><strong>No third-party ad targeting</strong> using children\'s personal data</li><li><strong>Parental access rights</strong> — parents can review, request deletion of, or restrict further collection of their child\'s data at any time</li><li><strong>Secure data storage</strong> with restricted internal access to student information</li><li><strong>Child-safe communication protocols</strong> — no direct child-to-stranger contact outside instructor-led, monitored sessions</li></ul>',
                'category' => 'Safety',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['sort_order' => $index + 1, 'is_active' => true])
            );
        }

        $templates = [
            ['slug' => 'welcome_student', 'name' => 'Welcome Student', 'subject' => 'Welcome to Younimatiq, {{student_name}}!', 'body' => '<p>Hi {{student_name}},</p><p>Welcome to Younimatiq!</p>'],
            ['slug' => 'enrollment_confirmation', 'name' => 'Enrollment Confirmation', 'subject' => 'Enrolled: {{course_title}}', 'body' => '<p>Hi {{student_name}}, you enrolled in {{course_title}} for ${{amount}}.</p>'],
            ['slug' => 'camp_booking_confirmation', 'name' => 'Camp Booking', 'subject' => 'Trial class booked!', 'body' => '<p>Hi {{parent_name}}, {{child_name}} is booked for {{camp_name}}.</p>'],
            ['slug' => 'class_reminder', 'name' => 'Class Reminder', 'subject' => 'Class reminder: {{course_title}}', 'body' => '<p>Hi {{student_name}}, class at {{class_time}} ({{timezone}}).</p>'],
            ['slug' => 'tutor_payout_summary', 'name' => 'Tutor Payout Summary', 'subject' => 'Monthly earnings: ${{amount}}', 'body' => '<p>Hi {{student_name}}, earnings: ${{amount}}.</p>'],
            ['slug' => 'camp_follow_up', 'name' => 'Camp Follow Up', 'subject' => 'How was the trial, {{parent_name}}?', 'body' => '<p>Hi {{parent_name}}, we hope {{child_name}} enjoyed {{camp_name}}!</p>'],
            ['slug' => 'qa_answered', 'name' => 'Q&A Answered', 'subject' => 'Your question was answered', 'body' => '<p>Hi {{student_name}}, your tutor answered your question about {{course_title}}.</p>'],
            ['slug' => 'review_request', 'name' => 'Review Request', 'subject' => 'How was {{course_title}}?', 'body' => '<p>Hi {{student_name}}, please leave a review for {{course_title}}!</p>'],
            ['slug' => 'welcome_tutor', 'name' => 'Welcome Tutor', 'subject' => 'Welcome, {{student_name}}!', 'body' => '<p>Hi {{student_name}}, your tutor application has been received.</p>'],
            ['slug' => 'abandoned_checkout', 'name' => 'Abandoned Checkout', 'subject' => 'Complete your enrollment: {{course_title}}', 'body' => '<p>Hi {{student_name}}, you left {{course_title}} in your cart (${{amount}}). <a href="{{checkout_url}}">Resume checkout</a>.</p>'],
            ['slug' => 'admin_payout_report', 'name' => 'Admin Payout Report', 'subject' => 'Monthly tutor payouts: ${{amount}}', 'body' => '<p>Hi {{student_name}}, total tutor payouts this month: ${{amount}}.</p>'],
        ];

        foreach ($templates as $tpl) {
            EmailTemplate::firstOrCreate(['slug' => $tpl['slug']], $tpl);
        }

        $blogs = [
            [
                'slug' => 'building-future-skills-from-robotics-to-public-speaking',
                'title' => 'Building Future Skills – From Robotics to Public Speaking',
                'excerpt' => 'The future belongs to students who can think, create, and communicate effectively. That\'s why modern learning platforms are combining technology with essential life skills.',
                'content' => '<p>The future belongs to students who can think, create, and communicate effectively. That\'s why modern learning platforms are combining technology with essential life skills like robotics, public speaking, and creative problem-solving.</p>',
                'featured_image' => '/storage/blogs/vZ4pC37n1MIQMtlXpwk8W8Zfwr0RkK0SXmoJZGL6.png',
                'published_at' => '2026-04-18',
            ],
            [
                'slug' => 'learning-beyond-books-how-minecraft-and-games-are-becoming-classrooms',
                'title' => 'Learning Beyond Books – How Minecraft and Games Are Becoming Classrooms',
                'excerpt' => 'For many children, Minecraft is a game. But in modern learning environments, it has become something much more powerful—a classroom where creativity meets curriculum.',
                'content' => '<p>For many children, Minecraft is a game. But in modern learning environments, it has become something much more powerful—a classroom where creativity meets curriculum.</p>',
                'featured_image' => '/storage/blogs/f4LpHwvqYHMPnPqVUgAo5l2NL49rEZksmFxbYxz8.png',
                'published_at' => '2026-04-18',
            ],
            [
                'slug' => 'why-kids-should-start-learning-coding-ai-and-creativity-early',
                'title' => 'Why Kids Should Start Learning Coding, AI, and Creativity Early',
                'excerpt' => 'Today\'s world is changing faster than ever, and children are no longer just consumers of technology—they are becoming creators.',
                'content' => '<p>Today\'s world is changing faster than ever, and children are no longer just consumers of technology—they are becoming creators. Platforms that teach coding, AI, and creativity early help kids build confidence and future-ready skills.</p>',
                'featured_image' => '/storage/blogs/IZbV3CQAMn50Q2JfASBJx8RF3IX00wLQhWdWuWlA.png',
                'published_at' => '2026-04-18',
            ],
        ];

        foreach ($blogs as $blog) {
            Blog::updateOrCreate(
                ['slug' => $blog['slug']],
                array_merge($blog, [
                    'status' => 'published',
                    'author' => 'Younimatiq',
                    'published_at' => \Carbon\Carbon::parse($blog['published_at']),
                ])
            );
        }
    }
}
