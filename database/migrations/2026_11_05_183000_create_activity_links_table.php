<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $existing = [
            ['Story Treasure Map', 'story-treasure-map', 'story-treasure-map.html'],
            ["Word Mining: Tyler's Story", 'word-mining-tylers-story', 'word-mining-tylers-story.html'],
            ['Friendship Quest', 'friendship-quest', 'friendship-quest.html'],
            ['Story Crafting Table', 'story-crafting-table', 'story-crafting-table.html'],
            ['Rebuild the Base', 'rebuild-the-base', 'rebuild-the-base.html'],
            ['Noun Miner', 'noun-miner', 'noun-miner.html'],
        ];

        $now = now();
        foreach ($existing as [$title, $slug, $filename]) {
            $source = public_path($filename);
            if (! is_file($source)) {
                continue;
            }
            $stored = 'activity-links/'.$slug.'.html';
            Storage::disk('local')->put($stored, file_get_contents($source));
            DB::table('activity_links')->insert([
                'title' => $title,
                'slug' => $slug,
                'file_path' => $stored,
                'original_filename' => $filename,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_links');
    }
};
