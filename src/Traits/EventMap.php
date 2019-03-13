<?php

namespace CodeGreenCreative\SamlIdp\Traits;

trait EventMap
{
    /**
     * All of the Horizon event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        'Illuminate\Auth\Events\Logout' => [
            'CodeGreenCreative\SamlIdp\Listeners\SamlLogout',
        ],
        'Illuminate\Auth\Events\Authenticated' => [
            'CodeGreenCreative\SamlIdp\Listeners\SamlRequest',
        ],
    ];
}
