<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['course_id', 'category_id']);
        });

        // Migrate existing data
        $courses = DB::table('courses')->whereNotNull('category_id')->get();
        foreach ($courses as $course) {
            DB::table('category_course')->insert([
                'course_id' => $course->id,
                'category_id' => $course->category_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop category_id from courses
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        });

        // Try to restore first category
        $relations = DB::table('category_course')->orderBy('id')->get()->groupBy('course_id');
        foreach ($relations as $courseId => $courseCategories) {
            DB::table('courses')
                ->where('id', $courseId)
                ->update(['category_id' => $courseCategories->first()->category_id]);
        }

        Schema::dropIfExists('category_course');
    }
};
