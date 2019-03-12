<?php

namespace CodeGreenCreative\SamlIdp;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'CodeGreenCreative\SamlIdp\Events\UserLoggedOut' => [
            'CodeGreenCreative\SamlIdp\Listeners\BroadcastSAMLLogout',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
