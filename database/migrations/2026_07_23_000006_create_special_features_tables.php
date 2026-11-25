<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. skill_trees table
        Schema::create('skill_trees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('total_levels')->default(4);
            $table->timestamps();
        });

        // 2. skill_nodes table
        Schema::create('skill_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_tree_id')->constrained('skill_trees')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->default('code');
            $table->integer('level_number')->default(1);
            $table->integer('order')->default(1);
            $table->timestamps();
        });

        // 3. student_skill_progress table
        Schema::create('student_skill_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('skill_node_id')->constrained('skill_nodes')->cascadeOnDelete();
            $table->enum('status', ['locked', 'in_progress', 'mastered'])->default('locked');
            $table->timestamp('mastered_at')->nullable();
            $table->timestamps();
        });

        // 4. student_portfolios table
        Schema::create('student_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('project_name');
            $table->string('project_url')->nullable();
            $table->string('screenshot_url')->nullable();
            $table->text('tutor_feedback')->nullable();
            $table->string('badge_earned')->nullable();
            $table->timestamps();
        });

        // 5. student_sticking_points table for Co-Pilot
        Schema::create('student_sticking_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->text('last_sticking_point')->nullable();
            $table->text('parent_notes')->nullable();
            $table->text('tutor_prep_notes')->nullable();
            $table->timestamps();
        });

        // 6. milestone_badges & student_milestones table
        Schema::create('milestone_badges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('badge_icon')->default('workspace_premium');
            $table->integer('level_number')->default(1);
            $table->timestamps();
        });

        Schema::create('student_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('milestone_badges')->cascadeOnDelete();
            $table->string('certificate_hash')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
        });

        // 7. referrals & sibling_discounts table
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->string('referral_code')->unique();
            $table->string('referred_email')->nullable();
            $table->decimal('reward_amount', 8, 2)->default(25.00);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sibling_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('primary_student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sibling_student_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('discount_percent', 5, 2)->default(15.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sibling_discounts');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('student_milestones');
        Schema::dropIfExists('milestone_badges');
        Schema::dropIfExists('student_sticking_points');
        Schema::dropIfExists('student_portfolios');
        Schema::dropIfExists('student_skill_progress');
        Schema::dropIfExists('skill_nodes');
        Schema::dropIfExists('skill_trees');
    }
};
