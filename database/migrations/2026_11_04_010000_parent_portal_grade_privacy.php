<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand role enum for parent accounts
        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','tutor','student','parent') NOT NULL DEFAULT 'student'");
        } catch (\Throwable $e) {
            // SQLite / already altered
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'grade')) {
                    $table->unsignedTinyInteger('grade')->nullable()->after('xp');
                }
                if (! Schema::hasColumn('users', 'leaderboard_opt_out')) {
                    $table->boolean('leaderboard_opt_out')->default(false)->after('grade');
                }
            });
        }

        if (! Schema::hasTable('parent_student')) {
            Schema::create('parent_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->string('relationship')->default('parent'); // parent|guardian
                $table->timestamp('consent_at')->nullable();
                $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['parent_id', 'student_id']);
            });
        }

        if (Schema::hasTable('student_portfolios')) {
            Schema::table('student_portfolios', function (Blueprint $table) {
                if (! Schema::hasColumn('student_portfolios', 'visibility')) {
                    $table->string('visibility')->default('private')->after('badge_earned'); // private|parents|public
                }
                if (! Schema::hasColumn('student_portfolios', 'status')) {
                    $table->string('status')->default('draft')->after('visibility'); // draft|pending|approved
                }
                if (! Schema::hasColumn('student_portfolios', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'leaderboard_opt_out')) {
                    $table->dropColumn('leaderboard_opt_out');
                }
                if (Schema::hasColumn('users', 'grade')) {
                    $table->dropColumn('grade');
                }
            });
        }

        if (Schema::hasTable('student_portfolios')) {
            Schema::table('student_portfolios', function (Blueprint $table) {
                foreach (['approved_at', 'status', 'visibility'] as $col) {
                    if (Schema::hasColumn('student_portfolios', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','tutor','student') NOT NULL DEFAULT 'student'");
        } catch (\Throwable $e) {
            //
        }
    }
};
