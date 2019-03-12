<?php

namespace CodeGreenCreative\SamlIdp;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
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
    public function boot(Router $router)
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/samlidp.php' => config_path('samlidp.php'),
        ], 'samlidp_config');
        // Load routes
        Route::group([
            'prefix' => 'saml',
            'namespace' => 'CodeGreenCreative\SamlIdp\Http\Controllers',
            'middleware' => 'web',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'samlidp');
        // Publish them as well
        // $this->publishes([
        //     __DIR__.'/../resources/views' => resource_path('views/vendor/samlidp'),
        // ], 'samlidp_views');
        // Add global middleware
        $router->aliasMiddleware(
            'samlidp',
            \CodeGreenCreative\SamlIdp\Http\Middleware\SamlRedirectIfAuthenticated::class
        );
        $this->loadBladeComponents();
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
     * [loadBladeComponents description]
     * @return [type] [description]
     */
    public function loadBladeComponents()
    {
        Blade::directive('samlidpinput', function ($expression) {
            if (request()->filled('SAMLRequest')) {
                return view('samlidp::components.input');
            }
        });
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerSamlidp()
    {
        $this->app->singleton('samlidp', function ($app) {
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
        $this->mergeConfigFrom(__DIR__.'/../config/samlidp.php', 'samlidp');
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
