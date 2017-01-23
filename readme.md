# Laravel 5 SAML idP

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-samlidp
```

After updating composer, add the ServiceProvider to the providers array in config/app.php

```php
Codegreencreative\Idp\SamlidpServiceProvider::class
```

## Blade directive
`@samlidpfields` will provide you with the hidden input fields supplied by your Service Provider. Place the directive in your blade file where your login form is located and right after the `<form>` tag.