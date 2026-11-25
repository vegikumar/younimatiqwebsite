<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tutor_training_progress')) {
            Schema::table('tutor_training_progress', function (Blueprint $table) {
                if (! Schema::hasColumn('tutor_training_progress', 'review_notes')) {
                    $table->text('review_notes')->nullable()->after('quiz_answers');
                }
                if (! Schema::hasColumn('tutor_training_progress', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('tutor_training_progress', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                if (! Schema::hasColumn('tutor_training_progress', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('reviewed_at');
                }
            });
        }

        if (! Schema::hasTable('tutor_match_suggestions')) {
            Schema::create('tutor_match_suggestions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->string('reason')->nullable();
                $table->string('status')->default('suggested'); // suggested|assigned|dismissed
                $table->decimal('match_score', 8, 2)->nullable();
                $table->timestamps();
                $table->index(['course_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_match_suggestions');

        if (Schema::hasTable('tutor_training_progress')) {
            Schema::table('tutor_training_progress', function (Blueprint $table) {
                if (Schema::hasColumn('tutor_training_progress', 'submitted_at')) {
                    $table->dropColumn('submitted_at');
                }
                if (Schema::hasColumn('tutor_training_progress', 'reviewed_at')) {
                    $table->dropColumn('reviewed_at');
                }
                if (Schema::hasColumn('tutor_training_progress', 'reviewed_by')) {
                    $table->dropConstrainedForeignId('reviewed_by');
                }
                if (Schema::hasColumn('tutor_training_progress', 'review_notes')) {
                    $table->dropColumn('review_notes');
                }
            });
        }
    }
};
