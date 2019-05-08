<p>
    <img height="45" src="docs/logo.png" alt="Logo">
</p>

[![Build Status](https://travis-ci.org/utopia-php/framework.svg?branch=master)](https://travis-ci.org/utopia-php/framework)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/framework.svg)
![License](https://img.shields.io/github/license/utopia-php/framework.svg)

Utopia Framework is a PHP MVC based framework with minimal must-have features for professional, simple, advanced and secure web development.

Utopia Framework is dependency free. Any extra features such as authentication, caching will be available as standalone models in order to keep the framework core as clean, light any easy to learn.

## Getting Started

Install using composer:
```bash
composer require utopia-php/framework
```

Init your first application:
```php
require_once __DIR__ . '/../../vendor/autoload.php';

use Utopia\App;
use Utopia\Request;
use Utopia\Response;

$request    = new Request();
$response   = new Response();

$utopia->get('/hello-world')
    ->action(
        function() use ($request, $response) {
            $response
              ->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->addHeader('Expires', '0')
              ->addHeader('Pragma', 'no-cache')
              ->json(['Hello' => 'World']);
        }
    );

$utopia->run($request, $response);
```

## System Requirements

Utopia Framework requires PHP 7.1 or later. We recommend using the latest PHP version whenever possible.

## More from Utopia

Library | Description
--- | ---
**[Utopia AB](https://github.com/utopia-php/ab)** | Simple PHP library for managing AB testing on the server side.
**[Utopia Abuse](https://github.com/utopia-php/abuse)** | Simple PHP library for rate limiting usage of different features in your app or API.
**[Utopia Cache](https://github.com/utopia-php/cache)** | Simple PHP library for managing cache with different storage adapters.
**[Utopia CLI](https://github.com/utopia-php/abuse)** | Simple PHP library for for building simple command line tools.
**[Utopia Locale](https://github.com/utopia-php/locale)** | Simple PHP library for adding support to multiple locales in your app or API.


## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
