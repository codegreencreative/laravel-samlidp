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
    public function boot(Router $router)
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/samlidp.php' => config_path('samlipd.php'),
        ]);

        // Register blade directives
        $this->bladeDirectives();

        $router->middleware('saml_guest', Http\Middleware\SamlRedirectIfAuthenticated::class);
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
        \Blade::directive('samlidpfields', function($expression) {
            $fields = '';
            if (request()->has('SAMLRequest')) {
                $fields .= '<input type="hidden" name="SAMLRequest" value="' . request()->get('SAMLRequest') . '" />';
            }
            if (request()->has('RelayState')) {
                $fields .= '<input type="hidden" name="RelayState" value="' . request()->get('RelayState') . '" />';
            }
            return $fields;
        });
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
        // return [
        //     'command.entrust.migration'
        // ];
    }
}