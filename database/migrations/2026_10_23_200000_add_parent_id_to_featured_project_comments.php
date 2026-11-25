<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('featured_project_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('featured_project_comments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('featured_project_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
