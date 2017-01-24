# Laravel 5 SAML idP

This package allows you to implement your own Identification Provider (idP) using the SAML 2.0 standard to be used with supporting SAML 2.0 Service Providers (SP).

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-samlidp
```

Add the ServiceProvider to the providers array in config/app.php

```php
Codegreencreative\Idp\SamlidpServiceProvider::class
```

Add Samlidp facade to the aliases array in config/app.php

```php
'Samlidp' => Codegreencreative\Idp\Facades\Samlidp::class
```

Publish samlidp.php config to config/samlidp.php

```shell
php artisan vendor:publish --provider="Codegreencreative\Idp\ServiceProvider"
```

Create a Self Signed Certificate (to be used later)

```shell
cd path/to/project/storage
openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout samlidp-private.key -out samlidp-public.key
```

Change the -days to what your application requires. `20 years = 7300`

## Usage

Add Samlidp fields to your login form

```blade
<form class="form-horizontal" role="form" method="POST" action="{{ url('/login') }}">

    {{ csrf_field() }}
    {!! Samlidp::fields() !!}
    ...

</form>
```

These fields will only be placed if a SAMLRequest is made.

## Config

After you publish the config file, you will need to set up your Service Providers. The key for the Service Provider is a base 64 encoded Consumer Service (ACS) URL. You can get this information from your Service Provider, but you will need to base 64 encode the URL and place it in your config. This is due to config dot notation.

For Facebook at Work, an example SAML URL may look like this: https://myfacebookworkplace.facebook.com/work/saml.php Base 64 encode this URL and place it in your config file. See example below.

Sample config/samlidp.php file

```php
<?php
return [

    // The URI to your login page
    'login_uri' => 'login',
    
    // The URI to the saml metadata file, this describes your idP
    'issuer_uri' => 'saml/metadata',

    'sp' => [
    
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
        
            // Your destination is the ACS URL of the Service Provider
            'destination' => 'https://myfacebookworkplace.facebook.com/work/saml.php',
        ]
    ]

];
```

## User requesting the SP but are not logged in to your idP...

In your `LoginController` you must be using `AuthenticatesUsers` trait shipped with Laravel. The `SamlidoAuth` trait will override the `authenticated` method to handle SAML form submissions. Upon successful login the `authenticated` method will fire causing the SAML form to be generated and submitted to the SP. Of course the original SAMLRequest would need to have been made for this process to execute. This is done for you. Just add the trait and use it and your done.

```php

use Codegreencreative\Idp\Traits\SamlidpAuth;

class LoginController extends Controller
{
    use AuthenticatesUsers, SamlidpAuth;
}
```

## User requesting the SP and are logged into your idP...

Upon request of the SP they will be redirected to your `login_uri` with a `SAMLRequest`. The middleware will recognize the request AND that the user is logged in.  Since the user is already authenticated, a form will be created and submitted back to the SP where the user will be logged in.

When adding the SamlidpServiceProvider, this middleware is installed for you by default.
