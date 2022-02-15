<p>
    <img height="45" src="docs/logo.png" alt="Logo">
</p>

[![Build Status](https://travis-ci.org/utopia-php/framework.svg?branch=master)](https://travis-ci.org/utopia-php/framework)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/framework.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://discord.gg/GSeTUeA)

Utopia Framework is a PHP MVC based framework with minimal must-have features for professional, simple, advanced and secure web development. This library is maintained by the [Appwrite team](https://appwrite.io).

Utopia Framework is dependency free. Any extra features such as authentication, caching will be available as standalone models in order to keep the framework core as clean, light and easy to learn.

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

App::get('/hello-world') // Define Route
    ->inject('request')
    ->inject('response')
    ->action(
        function($request, $response) {
            $response
              ->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->addHeader('Expires', '0')
              ->addHeader('Pragma', 'no-cache')
              ->json(['Hello' => 'World']);
        }
    );

App::setMode(App::MODE_TYPE_PRODUCTION); // Define Mode

$app        = new App('America/New_York');
$request    = new Request();
$response   = new Response();

$app->run($request, $response);
```

## System Requirements

Utopia Framework requires PHP 7.3 or later. We recommend using the latest PHP version whenever possible.

## More from Utopia

Our ecosystem support other thin PHP projects aiming to extend the core PHP Utopia framework.

Each project is focused on solving a single, very simple problem and you can use composer to include any of them in your next project. 

Library | Description
--- | ---
**[Utopia AB](https://github.com/utopia-php/ab)** | Simple PHP library for managing AB testing on the server side.
**[Utopia Abuse](https://github.com/utopia-php/abuse)** | Simple PHP library for rate limiting usage of different features in your app or API.
**[Utopia Analytics](https://github.com/utopia-php/analytics)** | Simple PHP library to send information about events or pageviews to Google Analytics.
**[Utopia Audit](https://github.com/utopia-php/audit)** | Simple PHP library for audit logging users actions and system events 
**[Utopia Cache](https://github.com/utopia-php/cache)** | Simple PHP library for managing cache with different storage adapters.
**[Utopia CLI](https://github.com/utopia-php/cli)** | Simple PHP library for for building simple command line tools.
**[Utopia Config](https://github.com/utopia-php/config)** | Simple PHP library for managing your app configuration.
**[Utopia Database](https://github.com/utopia-php/database)** | Simple PHP library for managing application persistency. It supports multiple database adapters. 
**[Utopia Domains](https://github.com/utopia-php/domains)** | Simple PHP library for parsing domain names.
**[Utopia Image](https://github.com/utopia-php/image)** | Simple PHP library for creating common image manipulations that is easy to use.
**[Utopia Locale](https://github.com/utopia-php/locale)** | Simple PHP library for adding support to multiple locales in your app or API.
**[Utopia Preloader](https://github.com/utopia-php/preloader)** | Simple PHP library for managing PHP preloading configuration.
**[Utopia Registry](https://github.com/utopia-php/registry)** | Simple PHP library for dependency injection and lazy loading of objects or resources.
**[Utopia System](https://github.com/utopia-php/system)** | Simple PHP library for obtaining information about the host's system.
**[Utopia Storage](https://github.com/utopia-php/storage)** | Simple and lite PHP library for managing application storage. It supports multiple storage adapters.

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

## Contributing

All code contributions - including those of people having commit access - must go through a pull request and approved by a core developer before being merged. This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send us a pull request.

You can refer to the [Contributing Guide](https://github.com/utopia-php/framework/blob/master/CONTRIBUTING.md) for more info.

For security issues, please email security@appwrite.io instead of posting a public issue in GitHub.

### Testing

  - `docker-compose up -d`
  - `docker-compose exec web  vendor/bin/phpunit --configuration phpunit.xml`
  - `docker-compose exec web vendor/bin/psalm --show-info=true`

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
