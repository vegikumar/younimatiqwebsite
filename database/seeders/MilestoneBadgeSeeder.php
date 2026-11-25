<?php

namespace Database\Seeders;

use App\Services\ClassRewardService;
use Illuminate\Database\Seeder;

class MilestoneBadgeSeeder extends Seeder
{
    public function run(): void
    {
        app(ClassRewardService::class)->ensureSystemBadges();

        $this->command?->info('Seeded '.count(ClassRewardService::SYSTEM_BADGES).' system badges for class rewards.');
    }
}
