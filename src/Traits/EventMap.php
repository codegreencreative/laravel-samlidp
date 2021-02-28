<?php

namespace Maghonemi\SamlIdp\Traits;

trait EventMap
{
    /**
     * All of the Laravel SAML IdP event / listener mappings.
     *
     * @var array
     */
    protected $events = [
        'Maghonemi\SamlIdp\Events\Assertion' => [],
        'Illuminate\Auth\Events\Logout' => [
            'Maghonemi\SamlIdp\Listeners\SamlLogout',
        ],
        'Illuminate\Auth\Events\Authenticated' => [
            'Maghonemi\SamlIdp\Listeners\SamlAuthenticated',
        ],
        'Illuminate\Auth\Events\Login' => [
            'Maghonemi\SamlIdp\Listeners\SamlLogin',
        ],
    ];
}
