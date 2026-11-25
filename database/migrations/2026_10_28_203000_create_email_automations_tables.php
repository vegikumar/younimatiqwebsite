<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_automations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->string('subject')->default('Trending classes this week');
            $table->string('heading')->default('Trending this week');
            $table->text('intro_text')->nullable();
            $table->string('cta_text')->default('See all classes');
            $table->string('cta_url')->nullable();
            $table->string('from_name')->default('Amy from Younimatiq');
            $table->string('sign_off_name')->default('Amy from Younimatiq');
            $table->string('reply_to')->nullable();
            $table->json('days_of_week')->nullable();
            $table->string('send_time', 5)->default('10:00');
            $table->string('timezone')->default('Asia/Kolkata');
            $table->json('audiences')->nullable();
            $table->string('course_source')->default('top');
            $table->json('course_ids')->nullable();
            $table->unsignedTinyInteger('max_courses')->default(4);
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('last_sent_count')->default(0);
            $table->timestamps();
        });

        Schema::create('email_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_automation_id')->constrained('email_automations')->cascadeOnDelete();
            $table->string('trigger')->default('schedule');
            $table->string('status')->default('queued');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('course_snapshot')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        DB::table('email_automations')->insert([
            'slug' => 'trending_classes',
            'name' => 'Trending Classes Digest',
            'is_enabled' => false,
            'subject' => 'Trending classes this week',
            'heading' => 'Trending this week',
            'intro_text' => 'The classes families are booking most right now:',
            'cta_text' => 'See all classes',
            'cta_url' => null,
            'from_name' => 'Amy from Younimatiq',
            'sign_off_name' => 'Amy from Younimatiq',
            'reply_to' => 'support@younimatiq.com',
            'days_of_week' => json_encode([1, 3, 5]),
            'send_time' => '10:00',
            'timezone' => 'Asia/Kolkata',
            'audiences' => json_encode(['students', 'newsletter']),
            'course_source' => 'top',
            'course_ids' => null,
            'max_courses' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_automation_runs');
        Schema::dropIfExists('email_automations');
    }
};
