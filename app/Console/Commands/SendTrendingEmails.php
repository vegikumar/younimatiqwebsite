<?php

namespace App\Console\Commands;

use App\Models\EmailAutomation;
use App\Services\EmailAutomationService;
use Illuminate\Console\Command;

class SendTrendingEmails extends Command
{
    protected $signature = 'email:send-trending
                            {--force : Send even if today is not a scheduled day/time}
                            {--test= : Send a single test email to this address}
                            {--id= : Target a specific automation id (with --force or --test)}';

    protected $description = 'Send scheduled email automations to their configured audiences';

    public function handle(EmailAutomationService $service): int
    {
        $testEmail = $this->option('test');
        $id = $this->option('id') ? (int) $this->option('id') : null;

        if ($testEmail) {
            $automation = $service->resolveAutomation($id);
            $result = $service->dispatch($automation, 'test', $testEmail);
            $this->info("Queued test email for [{$automation->name}] to {$testEmail} (run #{$result['run']->id}).");

            return self::SUCCESS;
        }

        if ($this->option('force')) {
            $query = EmailAutomation::query()->orderBy('id');
            if ($id) {
                $query->where('id', $id);
            }

            $queuedTotal = 0;
            foreach ($query->get() as $automation) {
                if (! $automation->is_enabled && ! $id) {
                    $this->line("Skipped disabled: {$automation->name}");
                    continue;
                }

                try {
                    $result = $service->dispatch($automation, 'manual');
                    $queuedTotal += $result['queued'];
                    $this->info("Queued {$result['queued']} for [{$automation->name}] (run #{$result['run']->id}).");
                } catch (\Throwable $e) {
                    $this->error("[{$automation->name}] ".$e->getMessage());
                }
            }

            $this->info("Force send complete. Total queued: {$queuedTotal}");

            return self::SUCCESS;
        }

        $queued = $service->runScheduled();
        if ($queued === 0) {
            $this->info('No automations were due in the current send window.');
        } else {
            $this->info("Queued {$queued} emails across due automations.");
        }

        return self::SUCCESS;
    }
}
