<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WebPushVapid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webpush:vapid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VAPID keys for Web Push';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!class_exists(\Minishlink\WebPush\VAPID::class)) {
            $this->error('minishlink/web-push is not installed.');
            return;
        }

        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        $this->setEnvironmentValue('VAPID_PUBLIC_KEY', $keys['publicKey']);
        $this->setEnvironmentValue('VAPID_PRIVATE_KEY', $keys['privateKey']);

        $this->info('VAPID keys generated successfully and written to .env!');
    }

    protected function setEnvironmentValue($environmentName, $configKey)
    {
        $path = base_path('.env');
        
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            if (str_contains($content, $environmentName . '=')) {
                $content = preg_replace(
                    "/^{$environmentName}=.*/m",
                    "{$environmentName}={$configKey}",
                    $content
                );
            } else {
                $content .= "\n{$environmentName}={$configKey}\n";
            }
            
            file_put_contents($path, $content);
        }
    }
}
