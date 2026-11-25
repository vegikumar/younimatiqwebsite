<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('city_country')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('source')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->boolean('is_18_plus')->default(false);
            $table->boolean('background_check_consent')->default(false);
            $table->boolean('child_safety_consent')->default(false);
            $table->json('subject_verticals')->nullable();
            $table->json('preferred_age_bands')->nullable();
            $table->string('teaching_experience')->nullable();
            $table->string('countries_taught')->nullable();
            $table->string('platforms_taught')->nullable();
            $table->string('higher_education')->nullable();
            $table->string('higher_institution')->nullable();
            $table->unsignedTinyInteger('english_fluency')->nullable();
            $table->text('about_yourself')->nullable();
            $table->string('additional_languages')->nullable();
            $table->string('availability_commitment')->nullable();
            $table->json('preferred_schedule')->nullable();
            $table->date('start_date')->nullable();
            $table->boolean('has_laptop_camera')->default(false);
            $table->boolean('has_stable_internet')->default(false);
            $table->string('internet_speed_screenshot_path')->nullable();
            $table->boolean('has_quiet_workspace')->default(false);
            $table->boolean('comfort_with_zoom')->default(false);
            $table->string('intro_video_path')->nullable();
            $table->json('certifications_paths')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('expected_hourly_rate')->nullable();
            $table->text('references')->nullable();
            $table->string('pipeline_status')->default('NEW'); // NEW, UNDER_REVIEW, VIDEO_REVIEWED, INTERVIEW, APPROVED, REJECTED
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('tutor_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->string('source')->default('admin'); // registration|training|admin
            $table->boolean('verified')->default(false);
            $table->timestamp('earned_at')->nullable();
            $table->timestamps();
            $table->unique(['tutor_id', 'skill_id']);
        });

        Schema::create('course_skill_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'skill_id']);
        });

        Schema::create('category_skill_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'skill_id']);
        });

        Schema::create('tutor_training_modules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tutor_training_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('tutor_training_modules')->cascadeOnDelete();
            $table->string('type'); // video|assignment|quiz
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_path')->nullable();
            $table->json('quiz_questions')->nullable(); // [{q, options[], answer}]
            $table->unsignedTinyInteger('pass_score')->default(70);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tutor_training_skill_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('tutor_training_modules')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['module_id', 'skill_id']);
        });

        Schema::create('tutor_training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('tutor_training_modules')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tutor_id', 'module_id']);
        });

        Schema::create('tutor_training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('tutor_training_items')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|in_progress|submitted|passed|failed
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('submission_text')->nullable();
            $table->string('submission_path')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['tutor_id', 'item_id']);
        });

        if (Schema::hasTable('email_automations') && ! Schema::hasColumn('email_automations', 'theme_html')) {
            Schema::table('email_automations', function (Blueprint $table) {
                $table->longText('theme_html')->nullable()->after('subject');
                $table->string('theme_path')->nullable()->after('theme_html');
            });
        }

        if (Schema::hasTable('email_templates') && ! Schema::hasColumn('email_templates', 'theme_html')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->longText('theme_html')->nullable();
                $table->string('theme_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_training_progress');
        Schema::dropIfExists('tutor_training_assignments');
        Schema::dropIfExists('tutor_training_skill_rewards');
        Schema::dropIfExists('tutor_training_items');
        Schema::dropIfExists('tutor_training_modules');
        Schema::dropIfExists('category_skill_requirements');
        Schema::dropIfExists('course_skill_requirements');
        Schema::dropIfExists('tutor_skills');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('tutor_profiles');

        if (Schema::hasTable('email_automations') && Schema::hasColumn('email_automations', 'theme_html')) {
            Schema::table('email_automations', function (Blueprint $table) {
                $table->dropColumn(['theme_html', 'theme_path']);
            });
        }
        if (Schema::hasTable('email_templates') && Schema::hasColumn('email_templates', 'theme_html')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn(['theme_html', 'theme_path']);
            });
        }
    }
};
