<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Syncs Meet API secret to the local Meet process (meet/app via PM2).
 * Meet API secret = shared key so only Laravel can create meetings / fetch recordings.
 */
class MeetConfigSync
{
    public function syncApiSecret(?string $secret): void
    {
        $secret = trim((string) $secret);
        if ($secret === '') {
            return;
        }

        $this->syncLaravelEnv($secret);

        $meetRoot = env('MEET_APP_ROOT')
            ?: (is_dir(base_path('meet/app')) ? base_path('meet/app') : '');

        if (! is_dir($meetRoot)) {
            Log::info('MeetConfigSync: Meet app directory missing; skipped Meet .env/pm2 sync.', [
                'path' => $meetRoot,
            ]);

            return;
        }

        $envPath = $meetRoot.'/.env';

        if (! is_writable($meetRoot) && ! (file_exists($envPath) && is_writable($envPath))) {
            Log::warning('MeetConfigSync: Meet directory not writable; skipped Meet .env sync.', [
                'path' => $meetRoot,
            ]);

            return;
        }

        $publicUrl = rtrim((string) (config('services.meet.base_url') ?: env('MEET_BASE_URL', 'https://younimatiq.com')), '/');
        $this->upsertEnvFile($envPath, [
            'MEET_PUBLIC_URL' => $publicUrl,
            'MEET_API_SECRET' => $secret,
        ]);

        $pm2Name = 'younimatiq-meet';
        $cmd = 'cd '.escapeshellarg($meetRoot).' && (pm2 restart '.$pm2Name.' --update-env >/dev/null 2>&1 || pm2 start ecosystem.config.cjs) && pm2 save';
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);
        if ($code !== 0) {
            Log::warning('Meet pm2 restart failed', ['code' => $code, 'out' => $out]);
        }
    }

    /**
     * @param  array<string, string>  $pairs
     */
    private function upsertEnvFile(string $envPath, array $pairs): void
    {
        $contents = file_exists($envPath) ? (string) file_get_contents($envPath) : '';
        foreach ($pairs as $key => $value) {
            $line = $key.'='.$value;
            if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $contents)) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $contents);
            } else {
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }
        file_put_contents($envPath, $contents);
    }

    private function syncLaravelEnv(string $secret): void
    {
        $laravelEnv = base_path('.env');
        if (! is_readable($laravelEnv) || ! is_writable($laravelEnv)) {
            return;
        }

        $contents = file_get_contents($laravelEnv);
        if ($contents === false) {
            return;
        }

        if (preg_match('/^MEET_API_SECRET=.*$/m', $contents)) {
            $contents = preg_replace('/^MEET_API_SECRET=.*$/m', 'MEET_API_SECRET='.$secret, $contents);
        } else {
            $contents .= "\nMEET_API_SECRET={$secret}\n";
        }

        file_put_contents($laravelEnv, $contents);
    }
}
