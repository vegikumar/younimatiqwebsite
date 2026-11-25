<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hr_audit_events')) {
            Schema::create('hr_audit_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type'); // training_approve|training_reject|training_revision|skill_grant|match_suggest|match_assign|match_dismiss
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->nullableMorphs('subject');
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->index(['event_type', 'created_at']);
            });
        }

        if (Schema::hasTable('tutor_skills') && ! Schema::hasColumn('tutor_skills', 'expires_at')) {
            Schema::table('tutor_skills', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('earned_at');
            });
        }

        if (Schema::hasTable('tutor_match_suggestions') && ! Schema::hasColumn('tutor_match_suggestions', 'score_breakdown')) {
            Schema::table('tutor_match_suggestions', function (Blueprint $table) {
                $table->json('score_breakdown')->nullable()->after('match_score');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_audit_events');
        if (Schema::hasTable('tutor_skills') && Schema::hasColumn('tutor_skills', 'expires_at')) {
            Schema::table('tutor_skills', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
        if (Schema::hasTable('tutor_match_suggestions') && Schema::hasColumn('tutor_match_suggestions', 'score_breakdown')) {
            Schema::table('tutor_match_suggestions', function (Blueprint $table) {
                $table->dropColumn('score_breakdown');
            });
        }
    }
};
