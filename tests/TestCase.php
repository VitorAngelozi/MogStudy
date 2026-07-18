<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->preventUnsafeTestingDatabase($app);

        return $app;
    }

    private function preventUnsafeTestingDatabase($app): void
    {
        if (! $app->environment('testing')) {
            return;
        }

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection === 'pgsql' && ! str_ends_with((string) $database, '_testing')) {
            throw new RuntimeException(
                "Refusing to run tests against PostgreSQL database [{$database}]. Use a *_testing database."
            );
        }
    }
}
