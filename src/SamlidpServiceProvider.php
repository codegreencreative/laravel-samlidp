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
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->registerEvents();
        $this->registerRoutes();
        $this->registerResources();
        $this->registerBladeComponents();

        $router->aliasMiddleware(
            'samlidp',
            \CodeGreenCreative\SamlIdp\Http\Middleware\SamlRedirectIfAuthenticated::class
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerServices();
        $this->registerCommands();
    }

    /**
     * [configure description]
     * @return [type] [description]
     */
    private function configure()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/samlidp.php', 'samlidp');
    }

    /**
     * [offerPublishing description]
     * @return [type] [description]
     */
    public function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/samlidp'),
            ], 'samlidp_views');

            $this->publishes([
                __DIR__.'/../config/samlidp.php' => config_path('samlidp.php'),
            ], 'samlidp_config');
        }
    }

    /**
     * [registerBladeComponents description]
     * @return [type] [description]
     */
    public function registerBladeComponents()
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
    private function registerServices()
    {
        $this->app->singleton('samlidp', function ($app) {
            return new Samlidp;
        });
    }

    /**
     * [registerEvents description]
     * @return [type] [description]
     */
    private function registerEvents()
    {
        # code...
    }

    /**
     * [registerRoutes description]
     * @return [type] [description]
     */
    private function registerRoutes()
    {
        Route::group([
            'prefix' => 'saml',
            'namespace' => 'CodeGreenCreative\SamlIdp\Http\Controllers',
            'middleware' => 'web',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * [registerResources description]
     * @return [type] [description]
     */
    private function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'samlidp');
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         FooCommand::class
        //     ]);
        // }
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
