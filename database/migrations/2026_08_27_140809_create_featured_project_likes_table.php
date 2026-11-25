<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_project_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('featured_project_id')->constrained('featured_projects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_key', 64);
            $table->timestamps();

            $table->unique(['featured_project_id', 'visitor_key'], 'project_visitor_like_unique');
            $table->index(['featured_project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_project_likes');
    }
};
