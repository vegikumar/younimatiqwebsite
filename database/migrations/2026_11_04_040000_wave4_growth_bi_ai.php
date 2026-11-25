<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parent_student') && ! Schema::hasColumn('parent_student', 'ai_assist_allowed')) {
            Schema::table('parent_student', function (Blueprint $table) {
                $table->boolean('ai_assist_allowed')->default(true)->after('consent_at');
            });
        }

        if (! Schema::hasTable('course_bundles')) {
            Schema::create('course_bundles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('discount_percent')->default(10);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_bundle_course')) {
            Schema::create('course_bundle_course', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_bundle_id')->constrained('course_bundles')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->unique(['course_bundle_id', 'course_id']);
            });
        }

        if (! Schema::hasTable('career_pathways')) {
            Schema::create('career_pathways', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->string('tagline')->nullable();
                $table->text('description')->nullable();
                $table->string('icon')->default('rocket_launch');
                $table->unsignedTinyInteger('min_grade')->default(6);
                $table->unsignedTinyInteger('max_grade')->default(12);
                $table->json('skill_tags')->nullable();
                $table->json('related_course_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_bundle_course');
        Schema::dropIfExists('course_bundles');
        Schema::dropIfExists('career_pathways');
        if (Schema::hasTable('parent_student') && Schema::hasColumn('parent_student', 'ai_assist_allowed')) {
            Schema::table('parent_student', function (Blueprint $table) {
                $table->dropColumn('ai_assist_allowed');
            });
        }
    }
};
