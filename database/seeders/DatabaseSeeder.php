<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\LiveSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users (Admin, Tutor, Student)
        $admin = User::firstOrCreate(['email' => 'admin@younimatiq.com'], [
            'name' => 'Admin User',
            'password' => Hash::make('younimatiq@school123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $tutor = User::firstOrCreate(['email' => 'tutor@younimatiq.com'], [
            'name' => 'Tutor John',
            'password' => Hash::make('password'),
            'role' => 'tutor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $student = User::firstOrCreate(['email' => 'student@younimatiq.com'], [
            'name' => 'Student Sarah',
            'password' => Hash::make('password'),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        // 2. Create Global Settings
        Setting::firstOrCreate(['key' => 'google_analytics_id'], ['value' => 'G-DEMO1234']);
        Setting::firstOrCreate(['key' => 'meta_pixel_id'], ['value' => '1234567890DEMO']);
        Setting::updateOrCreate(['key' => 'razorpay_key'], ['value' => 'rzp_test_YounimatiqDemo']);
        Setting::updateOrCreate(['key' => 'razorpay_secret'], ['value' => 'rzp_test_secret_replace_me']);

        // 3. Platform categories, pages, templates
        $this->call(PlatformDataSeeder::class);
        $this->call(AgeGroupSeeder::class);
        $this->call(MilestoneBadgeSeeder::class);

        // 4. Seed full course catalog from /courses thumbnails
        $this->call(CourseCatalogSeeder::class);
        $this->call(CourseReviewSeeder::class);

        // 5. Sample modules on first published live course (for dashboard demos)
        $sampleCourse = Course::where('status', 'published')->where('course_type', 'live')->first();
        if ($sampleCourse) {
            $module1 = CourseModule::firstOrCreate(
                ['course_id' => $sampleCourse->id, 'title' => 'Chapter 1: Welcome & Orientation'],
                [
                    'content_text' => 'Meet your instructor and set up for success.',
                    'content_type' => 'zoom',
                    'order' => 1,
                ]
            );

            LiveSession::firstOrCreate(['course_module_id' => $module1->id], [
                'course_id' => $sampleCourse->id,
                'zoom_meeting_id' => '123456789',
                'join_url' => 'https://zoom.us/j/123456789',
                'zoom_passcode' => 'YOUNIMATIQ',
                'start_time' => now()->addDays(1),
                'duration_minutes' => 60,
            ]);
        }

        // 6. Seed Dashboard Dummy Data
        $this->call(DashboardDataSeeder::class);
    }
}
