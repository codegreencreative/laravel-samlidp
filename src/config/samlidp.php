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

   'login_uri' => 'login',
   'issuer_uri' => 'saml/metadata',

    'sp' => [
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            'destination' => 'https://myfacebookworkplace.facebook.com/work/saml.php',
        ]
    ]

];