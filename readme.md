# Laravel 5 SAML idP

This package allows you to implement your own Identification Provider using the SAML 2.0 standard.

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-samlidp
```

After updating composer, add the ServiceProvider to the providers array in config/app.php

```php
Codegreencreative\Idp\SamlidpServiceProvider::class
```

Add Samlidp facade

```php
'Samlidp' => Codegreencreative\Idp\Facades\Samlidp::class
```

## Publish Config

```shell
php artisan vendor:publish --provider="Codegreencreative\Idp\ServiceProvider"
```

## Usage:

Add Samlidp fields to your login form

```blade
<form class="form-horizontal" role="form" method="POST" action="{{ url('/login') }}">

    {{ csrf_field() }}
    {!! Samlidp::fields() !!}
    ...

</form>
```

### 1. Create a Self Signed Certificate

```shell
cd path/to/project/storage
openssl req -x509 -sha256 -nodes -days 365 -newkey rsa:2048 -keyout samlidp-private.key -out samlidp-public.key
```

Change the -days to what your application requires. `20 years = 7300`

### Identification Provider (you) Issuer

The `login_uri` is by default using `login`. You may change this to your login page as needed.

The `issuer_uri` is the route where your metadata about your Identification Providers behavior.

### Service Provider Key

After you publish the config file, you will need to set up your Service Providers. The key for the Service Provider is a base 64 encoded consumer service URL. You can get this information from your Service Provider, but you will need to base 64 encode the URL and place it in your config. This is due to config dot notation.

For Facebook at Work, an example SAML URL may look like this: https://myfacebookworkplace.facebook.com/work/saml.php Base 64 encode this URL and place it in your config file. See example below.

### Service Provider Destination

 Your destination is the ACS URL of the Service Provider: https://myfacebookworkplace.facebook.com/work/saml.php

```php
<?php
return [

    'login_uri' => 'login',
    'issuer_uri' => 'saml/metadata',

    'sp' => [
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            'destination' => 'https://myfacebookworkplace.facebook.com/work/saml.php',
        ]
    ]

];
```

## Authenticated Users

In your `LoginController` you must be using `AuthenticatesUsers` trait shipped with Laravel. The `SamlidoAuth` trait will override the `authenticated` method to handle SAML form submissions.

```php
class LoginController extends Controller
{
    use AuthenticatesUsers, SamlidpAuth;
}
```