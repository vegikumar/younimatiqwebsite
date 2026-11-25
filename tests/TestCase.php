<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety guard: a cached production config overrides phpunit.xml env
        // vars and would point RefreshDatabase at the live MySQL database.
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException(
                'Tests must run against sqlite (:memory:). A cached production config is present. '.
                'Run `php artisan config:clear` before `php artisan test`.'
            );
        }

        $this->withoutVite();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['logging.default' => 'null']);

        $viewPath = sys_get_temp_dir().'/younimatiq-test-views';
        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0777, true);
        }
        config(['view.compiled' => $viewPath]);
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        $data['_token'] = 'testing-token';

        return $this->withSession(['_token' => 'testing-token'])
            ->call('POST', $uri, $data, $headers);
    }

    public function put($uri, array $data = [], array $headers = [])
    {
        $data['_token'] = 'testing-token';

        return $this->withSession(['_token' => 'testing-token'])
            ->call('PUT', $uri, $data, $headers);
    }

    public function patch($uri, array $data = [], array $headers = [])
    {
        $data['_token'] = 'testing-token';

        return $this->withSession(['_token' => 'testing-token'])
            ->call('PATCH', $uri, $data, $headers);
    }

    public function delete($uri, array $data = [], array $headers = [])
    {
        $data['_token'] = 'testing-token';

        return $this->withSession(['_token' => 'testing-token'])
            ->call('DELETE', $uri, $data, $headers);
    }
}
