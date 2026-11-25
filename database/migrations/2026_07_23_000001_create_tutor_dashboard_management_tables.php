<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update courses table
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'format')) {
                $table->string('format')->default('group')->after('course_type');
            }
            if (!Schema::hasColumn('courses', 'welcome_message')) {
                $table->text('welcome_message')->nullable()->after('description');
            }
            if (!Schema::hasColumn('courses', 'welcome_video_url')) {
                $table->string('welcome_video_url')->nullable()->after('welcome_message');
            }
        });

        // 2. Create course_units table
        Schema::create('course_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        // 3. Update course_modules table to support unit_id
        Schema::table('course_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('course_modules', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->constrained('course_units')->nullOnDelete()->after('course_id');
            }
        });

        // 4. Create tutor_availabilities table
        Schema::create('tutor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->tinyInteger('day_of_week')->nullable()->comment('0 = Sunday, 6 = Saturday');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('specific_date')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_booked')->default(false);
            $table->timestamps();
        });

        // 5. Create announcements table
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('video_url')->nullable();
            $table->boolean('is_welcome_post')->default(false);
            $table->string('status')->default('published')->comment('published, scheduled, draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // 6. Create announcement_reads table
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('tutor_availabilities');

        Schema::table('course_modules', function (Blueprint $table) {
            if (Schema::hasColumn('course_modules', 'unit_id')) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            }
        });

        Schema::dropIfExists('course_units');

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'format')) {
                $table->dropColumn('format');
            }
            if (Schema::hasColumn('courses', 'welcome_message')) {
                $table->dropColumn('welcome_message');
            }
            if (Schema::hasColumn('courses', 'welcome_video_url')) {
                $table->dropColumn('welcome_video_url');
            }
        });
    }
};
