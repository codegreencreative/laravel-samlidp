<?php

namespace Maghonemi\SamlIdp;

/**
 * The service provider for laravel-samleidp
 *
 * @license MIT
 */

use Maghonemi\SamlIdp\Console\CreateCertificate;
use Maghonemi\SamlIdp\Console\CreateServiceProvider;
use Maghonemi\SamlIdp\Traits\EventMap;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelSamlIdpServiceProvider extends ServiceProvider
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
     * Configure the service provider
     *
     * @return void
     */
    private function configure()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/samlidp.php', 'samlidp');
    }

    /**
     * Offer publishing for the service provider
     *
     * @return void
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
     * Register blade components for service provider
     *
     * @return void
     */
    public function registerBladeComponents()
    {
        Blade::directive('samlidp', function ($expression) {
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
    }

    /**
     * Loop through events and listeners provided by EventMap trait
     *
     * @return void
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
     * Register routes for the service provider
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::name('saml.')
            ->prefix('saml')
            ->namespace('Maghonemi\SamlIdp\Http\Controllers')
            ->middleware('web')->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
    }

    /**
     * Register resources for the service provider
     *
     * @return void
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
}
