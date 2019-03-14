<?php

namespace CodeGreenCreative\SamlIdp;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use CodeGreenCreative\SamlIdp\Commands\CreateCertificate;
use CodeGreenCreative\SamlIdp\Commands\CreateServiceProvider;
use CodeGreenCreative\SamlIdp\Traits\EventMap;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SamlidpServiceProvider extends ServiceProvider
{
    use EventMap;

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
        $this->mergeConfigFrom(__DIR__ . '/../config/samlidp.php', 'samlidp');
    }

    /**
     * [offerPublishing description]
     * @return [type] [description]
     */
    public function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/samlidp'),
            ], 'samlidp_views');

            $this->publishes([
                __DIR__ . '/../config/samlidp.php' => config_path('samlidp.php'),
            ], 'samlidp_config');

            // Create storage/samlidp directory
            if (!file_exists(storage_path() . "/samlidp")) {
                mkdir(storage_path() . "/samlidp", 0755, true);
            }
        }
    }

    /**
     * [registerBladeComponents description]
     * @return [type] [description]
     */
    public function registerBladeComponents()
    {
        Blade::directive('samlidp', function ($expression) {
            \Log::info($_GET['SAMLRequest'] ?? 'Nope');
            if (request()->filled('SAMLRequest')) {
                return "<?php echo view('samlidp::components.input'); ?>";
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
        $events = $this->app->make(Dispatcher::class);
        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
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
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * [registerResources description]
     * @return [type] [description]
     */
    private function registerResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'samlidp');
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateCertificate::class,
                CreateServiceProvider::class,
            ]);
        }
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'samlidp',
        ];
    }
}
