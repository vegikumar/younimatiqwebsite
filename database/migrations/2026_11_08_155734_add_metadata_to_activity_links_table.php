<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_links', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->string('subject')->nullable()->after('description');
            $table->string('grade')->nullable()->after('subject');
            $table->string('access', 20)->default('members')->after('grade');
            $table->string('status', 20)->default('published')->after('access');
            $table->string('thumbnail_path')->nullable()->after('original_filename');
        });
    }

    public function down(): void
    {
        Schema::table('activity_links', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'subject',
                'grade',
                'access',
                'status',
                'thumbnail_path',
            ]);
        });
    }
};
