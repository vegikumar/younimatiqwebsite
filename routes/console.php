<?php

use App\Jobs\AbandonedCheckoutReminderJob;
use App\Jobs\CampFollowUpJob;
use App\Jobs\TutorPayoutNotificationJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-session-reminders')->everyMinute();
Schedule::job(new AbandonedCheckoutReminderJob)->hourly();
Schedule::job(new \App\Jobs\AutoSendSessionRecapsJob)->everyFifteenMinutes();
Schedule::job(new CampFollowUpJob)->daily();
Schedule::job(new \App\Jobs\LockExpiredTrialsJob)->daily();
Schedule::job(new TutorPayoutNotificationJob)->monthlyOn(1, '09:00');
Schedule::command('email:send-trending')->everyFifteenMinutes();

Schedule::command('zoom:generate-links')->everyFiveMinutes();
Schedule::command('meet:sync-recordings')->everyFifteenMinutes();
Schedule::command('recordings:purge-expired')->dailyAt('02:30');
Artisan::command('app:optimize-production', function () {
    $this->call('config:cache');
    $this->call('route:cache');
    $this->call('view:cache');
    $this->info('Production caches built successfully.');
})->purpose('Cache config, routes, and views for production performance');
