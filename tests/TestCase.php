<?php declare(strict_types=1);

namespace CodeGreenCreative\SamlIdp\Tests;

use CodeGreenCreative\SamlIdp\LaravelSamlIdpServiceProvider;
use \Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelSamlIdpServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => 'postgres',
            'port' => '5432',
            'database' => 'testsuite',
            'username' => 'testsuite',
            'password' => 'testsuite',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]);
    }
}
