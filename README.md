<p>
    <img height="45" src="docs/logo.png" alt="Logo">
</p>

[![Build Status](https://travis-ci.org/utopia-php/http.svg?branch=master)](https://travis-ci.org/utopia-php/http)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/http.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://discord.gg/GSeTUeA)

Utopia HTTP is a PHP MVC based framework with minimal must-have features for professional, simple, advanced and secure web development. This library is maintained by the [Appwrite team](https://appwrite.io).

Utopia HTTP is dependency-free. Any extra features, such as authentication or caching are available as standalone models in order to keep the framework core clean, light, and easy to learn.

## Getting Started

Install using Composer:

```bash
composer require utopia-php/http
```

Init your first application in `src/server.php`:

```php
require_once __DIR__.'/../vendor/autoload.php';

use Utopia\Http\Http;
use Utopia\Http\Request;
use Utopia\Http\Response;
use Utopia\Http\Adapter\FPM\Server;

Http::get('/hello-world') // Define Route
    ->dependency('request')
    ->dependency('response')
    ->action(
        function(Request $request, Response $response) {
            $response
              ->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->addHeader('Expires', '0')
              ->addHeader('Pragma', 'no-cache')
              ->json(['Hello' => 'World']);
        }
    );

Http::setMode(Http::MODE_TYPE_PRODUCTION);

$http = new Http(new Server(), 'America/New_York');
$http->start();
```

Run HTTP server:

```bash
php -S localhost:8000 src/server.php 
```

Send HTTP request:

```bash
curl http://localhost:8000/hello-world
```

### Server Adapters

The library supports server adapters to be able to run on any PHP setup. You could use the FPM or Swoole server.

#### Use PHP FPM server

```php
use Utopia\Http\Http;
use Utopia\Http\Response;
use Utopia\Http\Adapter\FPM\Server;

Http::get('/')
    ->dependency('response')
    ->action(
        function(Response $response) {
            $response->send('Hello from PHP FPM');
        }
    );

$http = new Http(new Server(), 'America/New_York');
$http->start();
```

> When using PHP FPM, you can use the command `php -S localhost:80 src/server.php` to run the HTTP server locally

#### Using Swoole server

```php
use Utopia\Http\Http;
use Utopia\Http\Request;
use Utopia\Http\Response;
use Utopia\Http\Adapter\Swoole\Server;

Http::get('/')
    ->dependency('request')
    ->dependency('response')
    ->action(
        function(Request $request, Response $response) {
            $response->send('Hello from Swoole');
        }
    );

$http = new Http(new Server('0.0.0.0', '80'), 'America/New_York');
$http->start();
```

> When using Swoole, you can use the command `php src/server.php` to run the HTTP server locally, but you need Swoole installed. For setup with Docker, check out our [example application](/example)

### Parameters

Parameters are used to receive input into endpoint action from the HTTP request. Parameters could be defined as URL parameters or in a body with a structure such as JSON.

Every parameter must have a validator defined. Validators are simple classes that verify the input and ensure the security of inputs. You can define your own validators or use some of [built-in validators](/src/Http/Validator).

Define an endpoint with params:

```php
Http::get('/')
    ->param('name', 'World', new Text(256), 'Name to greet. Optional, max length 256.', true)
    ->dependency('response')
    ->action(function(string $name, Response $response) {
        $response->send('Hello ' . $name);
    });
```

Send HTTP requests to ensure the parameter works:

```bash
curl http://localhost:8000/hello-world
curl http://localhost:8000/hello-world?name=Utopia
curl http://localhost:8000/hello-world?name=Appwrite
```

It's always recommended to use params instead of getting params or body directly from the request resource. If you do that intentionally, always make sure to run validation right after fetching such a raw input.

### Hooks

There are three types of hooks:

- **Init hooks** are executed before the route action is executed
- **Shutdown hooks** are executed after route action is finished, but before application shuts down
- **Error hooks** are executed whenever there's an error in the application lifecycle.

You can provide multiple hooks for each stage. If you do not assign groups to the hook, by default, the hook will be executed for every route. If a group is defined on a hook, it will only run during the lifecycle of a request with the same group name on the action.

```php
Http::init()
    ->dependency('request')
    ->action(function(Request $request) {
        \var_dump("Recieved: " . $request->getMethod() . ' ' . $request->getURI());
    });

Http::shutdown()
    ->dependency('response')
    ->action(function(Response $response) {
        \var_dump('Responding with status code: ' . $response->getStatusCode());
    });

Http::error()
    ->dependency('error')
    ->dependency('response')
    ->action(function(\Throwable $error, Response $response) {
        $response
            ->setStatusCode(500)
            ->send('Error occurred ' . $error);
    });
```

Hooks are designed to be actions that run during the lifecycle of requests. Hooks should include functional logic. Hooks are not designed to prepare dependencies or context for the request. For such a use case, you should use resources.

### Groups

Groups allow you to define common behavior for multiple endpoints.

You can start by defining a group on an endpoint. Keep in mind you can also define multiple groups on a single endpoint.

```php
Http::get('/v1/health')
    ->groups(['api', 'public'])
    ->dependency('response')
    ->action(
        function(Response $response) {
            $response->send('OK');
        }
    );
```

Now you can define hooks that would apply only to specific groups. Remember, hooks can also be assigned to multiple groups.

```php
Http::init()
    ->groups(['api'])
    ->dependency('request')
    ->dependency('response')
    ->action(function(Request $request, Response $response) {
        $apiKey = $request->getHeader('x-api-key', '');

        if(empty($apiKey)) {
            $response
                ->setStatusCode(Response::STATUS_CODE_UNAUTHORIZED)
                ->send('API key missing.');
        }
    });
```

Groups are designed to be actions that run during the lifecycle of requests to endpoints that have some logic in common. Groups allow you to prevent code duplication and are designed to be defined anywhere in your source code to allow flexibility.

### Resources

Resources allow you to prepare dependencies for requests such as database connection or the user who sent the request. A new instance of a resource is created for every request.

Define a resource:

```php
Http::setResource('timing', function() {
    return \microtime(true);
});
```

Inject resource into endpoint action:

```php
Http::get('/')
    ->dependency('timing')
    ->dependency('response')
    ->action(function(float $timing, Response $response) {
        $response->send('Request Unix timestamp: ' . \strval($timing));
    });
```

Inject resource into a hook:

```php
Http::shutdown()
    ->dependency('timing')
    ->action(function(float $timing) {
        $difference = \microtime(true) - $timing;
        \var_dump("Request took: " . $difference . " seconds");
    });
```

In advanced scenarios, resources can also be injected into other resources or endpoint parameters.

Resources are designed to prepare dependencies or context for the request. Resources are not meant to do functional logic or return callbacks. For such a use case, you should use hooks.

To learn more about architecture and features for this library, check out more in-depth [Getting started guide](/docs/Getting-Starting-Guide.md).

## System Requirements

Utopia HTTP requires PHP 8.1 or later. We recommend using the latest PHP version whenever possible.

## More from Utopia

Our ecosystem supports other thin PHP projects aiming to extend the core PHP Utopia HTTP.

Each project is focused on solving a single, very simple problem and you can use composer to include any of them in your next project.

You can find all libraries in [GitHub Utopia organization](https://github.com/utopia-php).

## Contributing

All code contributions - including those of people having commit access - must go through a pull request and approved by a core developer before being merged. This is to ensure proper review of all the code.

Fork the project, create a feature branch, and send us a pull request.

You can refer to the [Contributing Guide](https://github.com/utopia-php/http/blob/master/CONTRIBUTING.md) for more info.

For security issues, please email security@appwrite.io instead of posting a public issue in GitHub.

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
