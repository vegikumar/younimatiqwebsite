<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Make user_id nullable so guests can submit reviews
            $table->foreignId('user_id')->nullable()->change();
            // Add reviewer name for anonymous/guest reviews
            $table->string('reviewer_name')->nullable()->after('user_id');
            // Add token for secure link generation
            $table->string('review_token')->nullable()->after('reviewer_name');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['reviewer_name', 'review_token']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
