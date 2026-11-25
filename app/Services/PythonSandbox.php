<?php

namespace App\Services;

class PythonSandbox
{
    public function available(): bool
    {
        $result = $this->command(['sudo', '-n', '/usr/local/bin/ym-python-run', '--ready']);

        return $result['exit_code'] === 0;
    }

    /**
     * @return array{ok: bool, available: bool, stdout: string, stderr: string, exit_code: int|null, duration_ms: int}
     */
    public function run(string $code, string $stdin): array
    {
        $started = microtime(true);
        if (! $this->available()) {
            return $this->payload(false, false, '', 'Python Lab is unavailable on this server.', null, $started);
        }

        $dir = sys_get_temp_dir().'/ym-py-'.bin2hex(random_bytes(8));
        $name = 'ympy'.bin2hex(random_bytes(6));
        if (! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            return $this->payload(false, true, '', 'Could not prepare a workspace.', null, $started);
        }

        try {
            file_put_contents($dir.'/main.py', $code);
            chmod($dir.'/main.py', 0644);

            $result = $this->command(
                ['sudo', '-n', '/usr/local/bin/ym-python-run', $dir, $name],
                $stdin,
                $name,
            );

            return $this->payload(
                $result['exit_code'] === 0,
                true,
                $result['stdout'],
                $result['stderr'],
                $result['exit_code'],
                $started,
            );
        } finally {
            @unlink($dir.'/main.py');
            @rmdir($dir);
        }
    }

    /**
     * @param  list<string>  $command
     * @return array{stdout: string, stderr: string, exit_code: int|null}
     */
    private function command(array $command, string $stdin = '', ?string $killName = null): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, null, [
            'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        ]);
        if (! is_resource($process)) {
            return ['stdout' => '', 'stderr' => 'Could not start Python.', 'exit_code' => null];
        }

        ignore_user_abort(true);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        $stdout = '';
        $stderr = '';
        $exit = null;
        $deadline = microtime(true) + 8;
        while (true) {
            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= (string) stream_get_contents($pipes[2]);
            $stdout = $this->cap($stdout);
            $stderr = $this->cap($stderr);
            $status = proc_get_status($process);
            if (connection_aborted()) {
                proc_terminate($process);
                $this->kill($killName);
                $exit = null;
                break;
            }
            if (! $status['running']) {
                $status = proc_get_status($process);
                $exit = $status['exitcode'];
                break;
            }
            if (microtime(true) > $deadline) {
                proc_terminate($process);
                $this->kill($killName);
                $stderr = $this->cap($stderr."\nTime limit exceeded");
                $exit = 124;
                break;
            }
            usleep(50000);
        }

        $stdout = $this->cap($stdout.(string) stream_get_contents($pipes[1]));
        $stderr = $this->cap($stderr.(string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $waited = proc_close($process);
        if ($exit === null || $exit === -1) {
            $exit = $waited;
        }

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exit_code' => $exit];
    }

    private function kill(?string $name): void
    {
        if (! $name || ! preg_match('/^ympy[a-f0-9]{12}$/', $name)) {
            return;
        }
        $process = proc_open(
            ['sudo', '-n', '/usr/local/bin/ym-python-kill', $name],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (! is_resource($process)) {
            return;
        }
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }

    private function cap(string $text): string
    {
        if (strlen($text) <= 65536) {
            return $text;
        }

        return substr($text, 0, 65536)."\n[output truncated]";
    }

    /**
     * @return array{ok: bool, available: bool, stdout: string, stderr: string, exit_code: int|null, duration_ms: int}
     */
    private function payload(bool $ok, bool $available, string $stdout, string $stderr, ?int $exit, float $started): array
    {
        return [
            'ok' => $ok,
            'available' => $available,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exit_code' => $exit,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }
}
