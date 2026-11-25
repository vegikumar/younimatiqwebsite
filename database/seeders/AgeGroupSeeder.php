<?php

namespace Database\Seeders;

use App\Models\AgeGroup;
use Illuminate\Database\Seeder;

class AgeGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Grade 1', 'slug' => '1', 'age_range' => '6-7', 'sort_order' => 1],
            ['name' => 'Grade 2', 'slug' => '2', 'age_range' => '7-8', 'sort_order' => 2],
            ['name' => 'Grade 3', 'slug' => '3', 'age_range' => '8-9', 'sort_order' => 3],
            ['name' => 'Grade 4', 'slug' => '4', 'age_range' => '9-10', 'sort_order' => 4],
            ['name' => 'Grade 5', 'slug' => '5', 'age_range' => '10-11', 'sort_order' => 5],
            ['name' => 'Grade 6', 'slug' => '6', 'age_range' => '11-12', 'sort_order' => 6],
            ['name' => 'Grade 7', 'slug' => '7', 'age_range' => '12-13', 'sort_order' => 7],
            ['name' => 'Grade 8', 'slug' => '8', 'age_range' => '13-14', 'sort_order' => 8],
            ['name' => 'Grade 9', 'slug' => '9', 'age_range' => '14-15', 'sort_order' => 9],
            ['name' => 'Grade 10', 'slug' => '10', 'age_range' => '15-16', 'sort_order' => 10],
            ['name' => 'Grade 11', 'slug' => '11', 'age_range' => '16-17', 'sort_order' => 11],
            ['name' => 'Grade 12', 'slug' => '12', 'age_range' => '17-18', 'sort_order' => 12],
        ];

        foreach ($groups as $group) {
            AgeGroup::updateOrCreate(
                ['slug' => $group['slug']],
                $group + ['description' => $group['name'].' live classes.', 'is_active' => true]
            );
        }
    }
}
