<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('age_groups') || ! Schema::hasTable('courses')) {
            return;
        }

        $bands = [
            ['slug' => '6-7',   'name' => '6 - 7 Years',   'age_range' => '6-7',   'sort_order' => 1],
            ['slug' => '7-8',   'name' => '7 - 8 Years',   'age_range' => '7-8',   'sort_order' => 2],
            ['slug' => '8-9',   'name' => '8 - 9 Years',   'age_range' => '8-9',   'sort_order' => 3],
            ['slug' => '9-10',  'name' => '9 - 10 Years',  'age_range' => '9-10',  'sort_order' => 4],
            ['slug' => '10-11', 'name' => '10 - 11 Years', 'age_range' => '10-11', 'sort_order' => 5],
            ['slug' => '11-12', 'name' => '11 - 12 Years', 'age_range' => '11-12', 'sort_order' => 6],
        ];

        foreach ($bands as $band) {
            DB::table('age_groups')->updateOrInsert(
                ['slug' => $band['slug']],
                [
                    'name' => $band['name'],
                    'age_range' => $band['age_range'],
                    'sort_order' => $band['sort_order'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Retire the old grade-based bands so they no longer appear in course pickers.
        DB::table('age_groups')
            ->whereIn('slug', ['k-2', '3-5', '6-8', '9-12'])
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Backfill the pivot from the existing single age_group column.
        if (Schema::hasTable('course_age_group')) {
            $map = DB::table('age_groups')->pluck('id', 'slug');

            DB::table('courses')
                ->whereNotNull('age_group')
                ->where('age_group', '!=', '')
                ->select('id', 'age_group')
                ->orderBy('id')
                ->chunk(500, function ($courses) use ($map) {
                    $rows = [];
                    foreach ($courses as $course) {
                        if (! isset($map[$course->age_group])) {
                            continue;
                        }
                        $rows[] = [
                            'course_id' => $course->id,
                            'age_group_id' => $map[$course->age_group],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    if (! empty($rows)) {
                        DB::table('course_age_group')->insertOrIgnore($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('age_groups')) {
            DB::table('age_groups')
                ->whereIn('slug', ['k-2', '3-5', '6-8', '9-12'])
                ->update(['is_active' => true]);
        }
    }
};
