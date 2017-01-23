<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAML idP configuration file
    |--------------------------------------------------------------------------
    |
    | Use this file to configure the service providers you want to use.
    |
    */

    'sp' => [
        'example=' => [
            'destination' => 'example.com',
            'issuer' => env('APP_URL') . '/saml/metadata',
            'cert' => resource_path('certs/id.crt'),
            'key' => resource_path('certs/id.key')
        ]
    ]

];