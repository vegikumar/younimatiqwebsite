<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ai_parent_notes table
        Schema::create('ai_parent_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('raw_bullets');
            $table->text('ai_generated_note');
            $table->text('homework_assignment')->nullable();
            $table->enum('status', ['draft', 'sent'])->default('draft');
            $table->timestamps();
        });

        // 2. ai_session_recaps table (with 4-hour edit window)
        Schema::create('ai_session_recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->nullable()->constrained('live_sessions')->nullOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->text('transcript_summary');
            $table->text('edited_summary')->nullable();
            $table->enum('status', ['pending_review', 'sent', 'skipped'])->default('pending_review');
            $table->timestamp('auto_send_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        // 3. ai_project_reviews table
        Schema::create('ai_project_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('project_title');
            $table->string('code_or_image_path')->nullable();
            $table->text('ai_progress_note');
            $table->timestamps();
        });

        // 4. tutor_payouts table
        Schema::create('tutor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->integer('period_month');
            $table->integer('period_year');
            $table->decimal('base_earnings', 10, 2)->default(0.00);
            $table->decimal('upsell_commission', 10, 2)->default(0.00);
            $table->decimal('bonus', 10, 2)->default(0.00);
            $table->decimal('net_payout', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->json('itemized_details')->nullable();
            $table->timestamps();
        });

        // 5. Update enrollments table to track upsell attribution
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'upsold_by_tutor_id')) {
                $table->foreignId('upsold_by_tutor_id')->nullable()->constrained('users')->nullOnDelete()->after('course_id');
            }
        });

        // 6. Update courses table for next level course recommendation
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'next_level_course_id')) {
                $table->foreignId('next_level_course_id')->nullable()->constrained('courses')->nullOnDelete()->after('category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'next_level_course_id')) {
                $table->dropForeign(['next_level_course_id']);
                $table->dropColumn('next_level_course_id');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'upsold_by_tutor_id')) {
                $table->dropForeign(['upsold_by_tutor_id']);
                $table->dropColumn('upsold_by_tutor_id');
            }
        });

        Schema::dropIfExists('tutor_payouts');
        Schema::dropIfExists('ai_project_reviews');
        Schema::dropIfExists('ai_session_recaps');
        Schema::dropIfExists('ai_parent_notes');
    }
};
