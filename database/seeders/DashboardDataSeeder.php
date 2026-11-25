<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Wishlist;
use App\Models\Review;
use App\Models\Order;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = User::where('role', 'student')->first();
        $tutor = User::where('role', 'tutor')->first();
        $courses = Course::all();

        if (!$student || !$tutor || $courses->isEmpty()) {
            return;
        }

        // 1. Seed Wishlist
        Wishlist::firstOrCreate([
            'user_id' => $student->id,
            'course_id' => $courses->first()->id,
        ]);

        // 2. Seed Reviews
        Review::firstOrCreate([
            'user_id' => $student->id,
            'course_id' => $courses->first()->id,
        ], [
            'rating' => 5,
            'comment' => 'This course is amazing! I learned so much about space and math at the same time. The tutor was super helpful.',
        ]);

        // 3. Seed Orders (Payment History)
        Order::firstOrCreate([
            'order_number' => 'ORD-99382',
        ], [
            'user_id' => $student->id,
            'course_id' => $courses->first()->id,
            'amount' => 49.99,
            'status' => 'completed',
            'payment_method' => 'card',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Order::firstOrCreate([
            'order_number' => 'ORD-99211',
        ], [
            'user_id' => $student->id,
            'course_id' => $courses->last()->id,
            'amount' => 199.99,
            'status' => 'completed',
            'payment_method' => 'paypal',
            'created_at' => Carbon::now()->subDays(15),
        ]);

        // 4. Seed Q&A
        $question = Question::firstOrCreate([
            'user_id' => $student->id,
            'course_id' => $courses->first()->id,
        ], [
            'title' => 'How do I calculate the trajectory of the rocket in Module 3?',
            'body' => 'I am struggling with the physics equation presented in the third video.',
        ]);

        Answer::firstOrCreate([
            'question_id' => $question->id,
            'user_id' => $tutor->id,
        ], [
            'body' => 'Great question! You need to use the gravity formula we discussed in chapter 2. Remember to account for the planet\'s mass. Check out the supplementary video I just added to the course materials!',
        ]);

        // 5. Seed Tutor Bookings
        DB::table('bookings')->insertOrIgnore([
            [
                'student_id' => $student->id,
                'course_id' => $courses->first()->id,
                'description' => '1-on-1 Help with Math',
                'zoom_join_url' => 'https://zoom.us/j/123456789',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'student_id' => $student->id,
                'course_id' => $courses->last()->id,
                'description' => 'Nature Walk Review',
                'zoom_join_url' => null,
                'status' => 'completed',
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ]
        ]);
    }
}
