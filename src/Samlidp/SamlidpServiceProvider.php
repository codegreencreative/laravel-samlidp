<?php

namespace Codegreencreative\Idp;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;


class SamlidpServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(\Illuminate\Contracts\Http\Kernel $kernel)
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/samlidp.php' => config_path('samlipd.php'),
        ]);
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
        // Register blade directives
        $this->bladeDirectives();
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'samlidp');
        // Add global middleware
        $kernel->prependMiddleware('Codegreencreative\Idp\Http\Middleware\SamlRedirectIfAuthenticated::class');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSamlidp();

        $this->mergeConfig();
    }

    /**
     * Register the blade directives
     *
     * @return void
     */
    private function bladeDirectives()
    {
        if (!class_exists('\Blade')) return;

        // @samlfields
        // \Blade::directive('samlidpfields', function($expression) {
        // });
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerSamlidp()
    {
        // $this->app->bind('entrust', function ($app) {
        //     return new Entrust($app);
        // });

        // $this->app->alias('entrust', 'Zizaco\Entrust\Entrust');
        //
        $this->app->singleton('samlidp', function($app) {
            return new Samlidp;
        });
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        // $this->app->singleton('command.entrust.migration', function ($app) {
        //     return new MigrationCommand();
        // });
    }

    /**
     * Merges user's and entrust's configs.
     *
     * @return void
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/samlidp.php', 'samlidp'
        );
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'samlidp'
        ];
    }
}