<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestone_badges', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->boolean('is_system')->default(false)->after('badge_icon');
            $table->foreignId('course_id')->nullable()->after('is_system')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('course_id')->constrained('users')->nullOnDelete();

            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('milestone_badges', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['created_by']);
            $table->dropUnique(['slug']);
            $table->dropIndex(['is_system']);
            $table->dropColumn(['slug', 'is_system', 'course_id', 'created_by']);
        });
    }
};
