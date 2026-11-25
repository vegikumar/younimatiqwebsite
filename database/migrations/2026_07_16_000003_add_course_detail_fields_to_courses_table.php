<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('importance')->nullable()->after('description');
            $table->json('learning_outcomes')->nullable()->after('importance');
            $table->json('highlights')->nullable()->after('learning_outcomes');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['importance', 'learning_outcomes', 'highlights']);
        });
    }
};
