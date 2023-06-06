[![Latest Version on Packagist](https://img.shields.io/packagist/v/codegreencreative/laravel-samlidp.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-samlidp)
[![Total Downloads](https://img.shields.io/packagist/dt/codegreencreative/laravel-samlidp.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-samlidp)

[Buy me a coffee](https://www.buymeacoffee.com/upwebdesign) :coffee:

# Laravel SAML IdP

This package allows you to implement your own Identification Provider (idP) using the SAML 2.0 standard to be used with supporting SAML 2.0 Service Providers (SP).

Starting in version ^5.1, Laravel 9 is supported.
Starting in version ^5.2.4, Laravel 10 is supported.

In this version we will be allowing for Laravel ^7.0 or ^8.0.

If you are looking for Laravel ^5.6 see [v1.0](https://github.com/codegreencreative/laravel-samlidp/tree/1.0)

If you are looking for Laravel ^6.0 use [v2.0](https://github.com/codegreencreative/laravel-samlidp/tree/2.0)

## Installation

```shell
composer require codegreencreative/laravel-samlidp
```

# Configuration

```shell
php artisan vendor:publish --tag="samlidp_config"
```

FileSystem configuration

```php
// config/filesystem.php

'disks' => [

        ...

        'samlidp' => [
            'driver' => 'local',
            'root' => storage_path() . '/samlidp',
        ]
],
```

Use the following command to create a self signed certificate for your IdP. If you change the certname or keyname to anything other than the default names, you will need to update your `config/samlidp.php` config file to reflect those new file names.

```shell
php artisan samlidp:cert [--days <days> --keyname <name> --certname <name>]
```

```shell
Options:
  --days=<days>      Days to add for the expiration date [default: 7800]
  --keyname=<name>   Name of the certificate key file [default: key.pem]
  --certname=<name>  Name of the certificate file [default: cert.pem]
```

Optionally, you can set the certificate and key using two environment variables: `SAMLIDP_CERT` and `SAMLIDP_KEY`.

## Usage

Within your login view, probably `resources/views/auth/login.blade.php` add the SAMLRequest directive beneath the CSRF directive:

```php
@csrf
@samlidp
```

The SAMLRequest directive will fill out the hidden input automatically when a SAMLRequest is sent by an HTTP request and therefore initiate a SAML authentication attempt. To initiate the SAML auth, the login and redirect processes need to be intervened. This is done using the Laravel events fired upon authentication.

## Config

After you publish the config file, you will need to set up your Service Providers. The key for the Service Provider is a base 64 encoded Consumer Service (ACS) URL. You can get this information from your Service Provider, but you will need to base 64 encode the URL and place it in your config. This is due to config dot notation.

You may use this command to help generate a new SAML Service Provider:

```shell
php artisan samlidp:sp
```

Example SP in `config/samlidp.php` file:

```php
<?php

return [
    // The URI to your login page
    'login_uri' => 'login',
    // The URI to the saml metadata file, this describes your idP
    'issuer_uri' => 'saml/metadata',
    // List of all Service Providers
    'sp' => [
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            // ACS URL of the Service Provider
            'destination' => 'https://example.com/saml/acs',
            // Simple Logout URL of the Service Provider
            'logout' => 'https://example.com/saml/sls',
            // SP certificate
            // 'certificate' => '',
            // Turn off auto appending of the idp query param
            // 'query_params' => false,
            // Turn off the encryption of the assertion per SP
            // 'encrypt_assertion' => false
        ],
    ],
    // List of guards saml idp will catch Authenticated, Login and Logout events (thanks @abublihi)
    'guards' => ['web'],
];
```

### Setting the service provider certificate

There are three options to set the service provider certificate.

1. Provide the certificate as a string:

```php
<?php

return [
    // ...
    'sp' => [
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            // ...
            // SP certificate
            // 'certificate' => "-----BEGIN CERTIFICATE-----\nb3BlbnNzaC1rZXktdjEA...LWdlbmVyYXRlZC1rZXkBAgM\n-----END CERTIFICATE-----"
        ],
    ],
    // ...
];
```

2. Load from a variable within the `.env` file.
   You can choose an appropriate variable name that best matches your projects requirements.

```php
<?php

return [
    // ...
    'sp' => [
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            // ...
            // SP certificate
            // 'certificate' => env('SAML_SP_CERTIFICATE', '')
        ],
    ],
    // ...
];
```

3. Load the certificate from a file:

```php
<?php

return [
    // ...
    'sp' => [
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            // ...
            // SP certificate
            // 'certificate' => 'file://' . storage_path('samlidp/service-provider.pem')
        ],
    ],
    // ...
];
```

## Log out of IdP after SLO

If you wish to log out of the IdP after SLO has completed, set `LOGOUT_AFTER_SLO` to `true` in your `.env` perform the logout action on the Idp.

```
// .env

LOGOUT_AFTER_SLO=true
```

## Redirect to SLO initiator after logout

If you wish to return the user back to the SP by which SLO was initiated, you may provide an additional query parameter to the `/saml/logout` route, for example:

```
https://idp.com/saml/logout?return_to=mysp.com
```

After all SP's have been logged out of, the user will be redirected to `mysp.com`. For this to work properly you need to add the `sp_slo_redirects` option to your `config/samlidp.php` config file, for example:

```php
<?php

// config/samlidp.php

return [
    // If you need to redirect after SLO depending on SLO initiator
    // key is beginning of HTTP_REFERER value from SERVER, value is redirect path
    'sp_slo_redirects' => [
        'mysp.com' => 'https://mysp.com',
    ],
];
```

## Attributes (optional)

Service providers may require more additional attributes to be sent via assertion. Its even possible that they require the same information but as a different Claim Type.

By Default this package will send the following Claim Types:

`ClaimTypes::EMAIL_ADDRESS` as `auth()->user()->email`
`ClaimTypes::GIVEN_NAME` as `auth()->user()->name`

This is because Laravel migrations, by default, only supply email and name fields that are usable by SAML 2.0.

To add additional Claim Types, you can subscribe to the Assertion event:

`CodeGreenCreative\SamlIdp\Events\Assertion`

Subscribing to the Event:

In your `App\Providers\EventServiceProvider` class, add to the already existing `$listen` property...

```php
protected $listen = [
    'App\Events\Event' => [
        'App\Listeners\EventListener',
    ],
    'CodeGreenCreative\SamlIdp\Events\Assertion' => [
        'App\Listeners\SamlAssertionAttributes'
    ]
];
```

Sample Listener:

```php
<?php

namespace App\Listeners;

use LightSaml\ClaimTypes;
use LightSaml\Model\Assertion\Attribute;
use CodeGreenCreative\SamlIdp\Events\Assertion;

class SamlAssertionAttributes
{
    public function handle(Assertion $event)
    {
        $event->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::PPID, auth()->user()->id))
            ->addAttribute(new Attribute(ClaimTypes::NAME, auth()->user()->name));
    }
}
```

## Digest Algorithm (optional)

See `\RobRichards\XMLSecLibs\XMLSecurityDSig` for all digest options.

```php
<?php

return [
    // Defind what digital algorithm you want to use
    'digest_algorithm' => \RobRichards\XMLSecLibs\XMLSecurityDSig::SHA1,
];
```

[Buy me a coffee](https://www.buymeacoffee.com/upwebdesign) :coffee:
