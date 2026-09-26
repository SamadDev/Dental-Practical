<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the app, then make sure we are not about to run the suite against
     * the real clinic database.
     *
     * phpunit.xml points the suite at an in-memory SQLite database, but every
     * `env()` call is bypassed while a config cache exists (`php artisan
     * config:cache`), which would silently send migrations and writes to the
     * development database. Fail loudly instead.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $database = (string) $app['config']->get(
            'database.connections.' . $app['config']->get('database.default') . '.database'
        );

        if ($app->environment('testing')
            && ! str_contains($database, ':memory:')
            && str_contains($database, 'database.sqlite')) {
            throw new RuntimeException(
                'Refusing to run tests against the development database ('.$database.'). '
                . 'Run `php artisan config:clear` and retry — a stale config cache freezes '
                . 'the phpunit.xml database overrides.'
            );
        }

        return $app;
    }
}
