<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users table with storefront & policy training columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('users', 'teaching_style')) {
                $table->text('teaching_style')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'intro_video_url')) {
                $table->string('intro_video_url')->nullable()->after('teaching_style');
            }
            if (!Schema::hasColumn('users', 'policy_training_completed_at')) {
                $table->timestamp('policy_training_completed_at')->nullable()->after('intro_video_url');
            }
            if (!Schema::hasColumn('users', 'nps_score')) {
                $table->decimal('nps_score', 4, 2)->default(9.50)->after('policy_training_completed_at');
            }
        });

        // 2. Create interested_leads table for re-engagement nudges
        Schema::create('interested_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['interested', 'nudged', 'enrolled'])->default('interested');
            $table->timestamp('last_nudged_at')->nullable();
            $table->timestamps();
        });

        // 3. Create policy_trainings table
        Schema::create('policy_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->string('module_slug'); // content_standards, safety_privacy
            $table->timestamp('completed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_trainings');
        Schema::dropIfExists('interested_leads');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nps_score')) {
                $table->dropColumn(['bio', 'teaching_style', 'intro_video_url', 'policy_training_completed_at', 'nps_score']);
            }
        });
    }
};
