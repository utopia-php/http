# Utopia Framework

Utopia Framework is a PHP MVC based framework with minimal must-have features for professional, simple, advanced and secure web development.

Utopia Framework is dependency free. Any extra features such as authentication, caching will be available as standalone models in order to keep the framework core as clean, light any easy to learn.

## Getting Started

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
Utopia Framework requires PHP 5.4 or later. We recommend using the latest PHP version whenever possible.

## Versioning

For transparency and insight into our release cycle, and for striving to maintain backward compatibility, Utopia PHP Framework will be maintained under the Semantic Versioning guidelines as much as possible. Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

For more information on SemVer, please visit [http://semver.org/](http://semver.org/).

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

## Links
* GIT Repositiory: [https://github.com/utopia-php/utopia](https://github.com/utopia-php/utopia)
* Contributors: [https://github.com/utopia-php/utopia/contributors](https://github.com/utopia-php/utopia/contributors)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
