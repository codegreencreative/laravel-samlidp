# Laravel SAML idP

This package allows you to implement your own Identification Provider (idP) using the SAML 2.0 standard to be used with supporting SAML 2.0 Service Providers (SP).

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-samlidp
```

Laravel 5.5+ shoudl auto discover the package, if not, run

```shell
php artisan package:discover
```

Publish config
config/samlidp.php

```shell
php artisan vendor:publish --tag="samlidp_config"
```

FileSystem configuration
Within `config/filesystem.php` following entry needs to be added:
```php
'disks' => [

    ...

    'samlidp' => [
        'driver' => 'local',
        'root' => storage_path() . '/samlidp',
    ],

],
```

# Create a Self Signed Certificate (to be used later)

Next we will create the necessary storage path and certificate files

```shell
mkdir -p storage/samlidp
touch storage/samlidp/{cert.pem,key.pem}
# Then
cd storage/samlidp
openssl req -x509 -sha256 -nodes -days 7300 -newkey rsa:2048 -keyout key.pem -out cert.pem
```

Change the -days to what your application requires. `20 years = 7300`

## Usage

Within your login view, problably resources/views/auth/login.blade.php add a SAMLRequest field beneath the CSRF field:
```php
@csrf
@samlidpinput
```
The SAMLRequest field will be filled automatically when a SAMLRequest is sent by a http request and therefore initiate a SAML authentication attempt. To initiate the SAML auth, the login and redirect functions need to be modified. First, open `App\Http\Controllers\Auth\LoginController` and add the `SamlIdpAuth` trait and override the `authenticated` method.

In your login controller remove
```php
use Illuminate\Foundation\Auth\AuthenticatesUsers;
```
with
```php
use CodeGreenCreative\SamlIdp\Traits\AuthenticatesUsers;
```

To allow later direct redirection when somebody is already logged in, we need to add also some lines to `App\Http\Middleware\RedirectIfAuthenticated`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use CodeGreenCreative\SamlIdp\Traits\SamlIdpAuth;

class RedirectIfAuthenticated
{
    use SamlAuth;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check() && $request->has('SAMLRequest') && ! $request->is('saml/logout')) {
            return response($this->samlRequest($request, Auth::user()), 200);
        }

        if (Auth::guard($guard)->check() && ! $request->is('saml/logout')) {
            return redirect('/home');
        }

        return $next($request);
    }
}
```

Update App\Http\Kernel protected $routeMiddleware with new `saml` middleware.

```php
protected $routeMiddleware = [

    ...

    'saml' => \CodeGreenCreative\SamlIdp\Http\Middleware\SamlRedirectIfAuthenticated::class
];
```

Update LoginController with new middleware

```php
$this->middleware('saml');
```

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
    // Get self signed certificate
    'crt' => storage_path('certs/samlidp.crt'),
    // Get private key
    'key' => storage_path('certs/samlidp.key'),
    // list of all service providers
    'sp' => [
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            // Your destination is the ACS URL of the Service Provider
            'destination' => 'https://myfacebookworkplace.facebook.com/work/saml.php',
        ]
    ]

];
```
