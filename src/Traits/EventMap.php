<?php

namespace CodeGreenCreative\SamlIdp\Traits;

trait EventMap
{
    /**
     * All of the Laravel SAML IdP event / listener mappings.
     *
     * @var array
     */
    protected $default_events = [
        'CodeGreenCreative\SamlIdp\Events\Assertion' => [],
        'Illuminate\Auth\Events\Logout' => [
            'CodeGreenCreative\SamlIdp\Listeners\SamlLogout',
        ],
        'Illuminate\Auth\Events\Authenticated' => [
            'CodeGreenCreative\SamlIdp\Listeners\SamlAuthenticated',
        ],
        'Illuminate\Auth\Events\Login' => [
            'CodeGreenCreative\SamlIdp\Listeners\SamlLogin',
        ],
    ];
}
