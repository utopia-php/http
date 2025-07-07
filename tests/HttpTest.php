<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Throwable;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Tests\MockRequest as Request;
use Utopia\Http\Tests\MockResponse as Response;
use Utopia\Http\Validator\Text;
use Utopia\Http\Adapter\FPM\Server;

class HttpTest extends TestCase
{
    protected ?Http $http;

    protected Container $context;

    protected ?string $method;

    protected ?string $uri;

    public function setUp(): void
    {
        Http::reset();

        $this->context = new Container();

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(fn () => new Request());

        $response = new Dependency();
        $response
            ->setName('response')
            ->setCallback(fn () => new Response());

        $this->context
            ->set($request)
            ->set($response);

        $this->http = new Http(new Server(), $this->context, 'Asia/Tel_Aviv');

        $this->http->setRequestClass(Request::class);
        $this->http->setResponseClass(Response::class);

        $this->saveRequest();
    }

    public function tearDown(): void
    {
        $this->http = null;
        $this->restoreRequest();
    }

    protected function saveRequest(): void
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->uri = $_SERVER['REQUEST_URI'] ?? null;
    }

    protected function restoreRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = $this->method;
        $_SERVER['REQUEST_URI'] = $this->uri;
    }

    public function testCanGetDifferentModes(): void
    {
        $this->assertEmpty(Http::getMode());
        $this->assertFalse(Http::isProduction());
        $this->assertFalse(Http::isDevelopment());
        $this->assertFalse(Http::isStage());

        Http::setMode(Http::MODE_TYPE_PRODUCTION);

        $this->assertEquals(Http::MODE_TYPE_PRODUCTION, Http::getMode());
        $this->assertTrue(Http::isProduction());
        $this->assertFalse(Http::isDevelopment());
        $this->assertFalse(Http::isStage());

        Http::setMode(Http::MODE_TYPE_DEVELOPMENT);

        $this->assertEquals(Http::MODE_TYPE_DEVELOPMENT, Http::getMode());
        $this->assertFalse(Http::isProduction());
        $this->assertTrue(Http::isDevelopment());
        $this->assertFalse(Http::isStage());

        Http::setMode(Http::MODE_TYPE_STAGE);

        $this->assertEquals(Http::MODE_TYPE_STAGE, Http::getMode());
        $this->assertFalse(Http::isProduction());
        $this->assertFalse(Http::isDevelopment());
        $this->assertTrue(Http::isStage());
    }

    public function testCanExecuteRoute(): void
    {
        $context = clone $this->context;

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: ' . $error->getMessage() . ' on file: ' . $error->getFile() . ' on line: ' . $error->getLine();
            });

        // Default Params
        $route = $this->http->addRoute('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->param('y', 'y-def', new Text(200), 'y param', true)
            ->action(function ($x, $y) {
                echo $x . '-' . $y;
            });

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request([]);
                $request->setURI('/path');
                $request->setMethod('GET');
                return $request;
            });

        $context
            ->set($request);

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def', $result);
    }

    public function testCanExecuteRouteWithParams(): void
    {
        $context = clone $this->context;

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request(['x' => 'param-x', 'y' => 'param-y', 'z' => 'param-z']);
                $request->setURI('/test-params');
                $request->setMethod('GET');
                return $request;
            });

        $rand = new Dependency();
        $rand
            ->setName('rand')
            ->setCallback(function () {
                return rand(0, 1000);
            });

        $context
            ->set($request)
            ->set($rand);

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: ' . $error->getMessage();
            });

        $route = $this->http->addRoute('GET', '/test-params');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->param('y', 'y-def', new Text(200), 'y param', true)
            ->inject('rand')
            ->param('z', 'z-def', function ($rand) {
                echo $rand . '-';

                return new Text(200);
            }, 'z param', true, ['rand'])
            ->action(function ($x, $y, $z, $rand) {
                echo $x . '-', $y;
            });

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();
        $resource = $context->get('rand');
        $this->assertEquals($resource . '-param-x-param-y', $result);
    }

    public function testCanExecuteRouteWithParamsWithError(): void
    {
        $route = $this->http->addRoute('GET', '/test-params-error');

        $route
            ->param('x', 'x-def', new Text(1, min: 0), 'x param', false)
            ->param('y', 'y-def', new Text(1, min: 0), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '-', $y;
            });

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: ' . $error->getMessage();
            });

        \ob_start();
        $context = clone $this->context;

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request(['x' => 'param-x', 'y' => 'param-y']);
                $request->setURI('/test-params-error');
                $request->setMethod('GET');
                return $request;
            });

        $rand = new Dependency();
        $rand
            ->setName('rand')
            ->setCallback(function () {
                return rand(0, 1000);
            });

        $context
            ->set($request)
            ->set($rand);

        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error: Invalid `x` param: Value must be a valid string and no longer than 1 chars', $result);
    }

    public function testCanExecuteRouteWithParamsWithHooks(): void
    {
        $context = clone $this->context;

        $this->http
            ->init()
            ->inject('rand')
            ->action(function ($rand) {
                echo 'init-' . $rand . '-';
            });

        $this->http
            ->shutdown()
            ->desc('global shutdown')
            ->action(function () {
                echo '-shutdown';
            });

        $this->http
            ->init()
            ->groups(['api'])
            ->action(function () {
                echo '(init-api)-';
            });

        $this->http
            ->shutdown()
            ->desc('api shutdown')
            ->groups(['api'])
            ->action(function () {
                echo '-(shutdown-api)';
            });

        $this->http
            ->init()
            ->groups(['homepage'])
            ->action(function () {
                echo '(init-homepage)-';
            });

        $this->http
            ->shutdown()
            ->groups(['homepage'])
            ->action(function () {
                echo '-(shutdown-homepage)';
            });

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: ' . $error->getMessage();
            });

        $route = $this->http->addRoute('GET', '/path-1');

        $route
            ->groups(['api'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '-', $y;
            });

        $homepage = $this->http->addRoute('GET', '/path-2');

        $homepage
            ->groups(['homepage'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '*', $y;
            });

        \ob_start();

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request(['x' => 'param-x', 'y' => 'param-y']);
                $request->setURI('/path-1');
                $request->setMethod('GET');
                return $request;
            });

        $rand = new Dependency();
        $rand
            ->setName('rand')
            ->setCallback(function () {
                return rand(0, 1000);
            });

        $context
            ->set($request)
            ->set($rand);

        $resource = $context->get('rand');
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-' . $resource . '-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        $context = clone $this->context;

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request(['x' => 'param-x', 'y' => 'param-y']);
                $request->setURI('/path-2');
                $request->setMethod('GET');
                return $request;
            });

        $rand = new Dependency();
        $rand
            ->setName('rand')
            ->setCallback(function () {
                return rand(0, 1000);
            });

        $context
            ->set($request)
            ->set($rand);

        $resource = $context->get('rand');
        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-' . $resource . '-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
    }

    public function testCanAddAndExecuteHooks()
    {
        $context = clone $this->context;

        $this->http
            ->init()
            ->action(function () {
                echo '(init)-';
            });

        $this->http
            ->shutdown()
            ->action(function () {
                echo '-(shutdown)';
            });

        // Default Params
        $route = $this->http->addRoute('GET', '/path-3');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->action(function ($x) {
                echo $x;
            });

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request([]);
                $request->setURI('/path-3');
                $request->setMethod('GET');
                return $request;
            });

        $context
            ->set($request);

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();
        $this->assertEquals('(init)-x-def-(shutdown)', $result);

        // Default Params
        $route = $this->http->addRoute('GET', '/path-4');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->hook(false)
            ->action(function ($x) {
                echo $x;
            });

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request([]);
                $request->setURI('/path-4');
                $request->setMethod('GET');
                return $request;
            });

        $context
            ->set($request)
        ;

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def', $result);
    }

    public function testAllowRouteOverrides()
    {
        Http::setAllowOverride(false);
        $this->assertFalse(Http::getAllowOverride());
        Http::get('/')->action(function () {
            echo 'Hello first';
        });

        try {
            Http::get('/')->action(function () {
                echo 'Hello second';
            });
            $this->fail('Failed to throw exception');
        } catch (\Exception $e) {
            // Threw exception as expected
            $this->assertEquals('Route for (GET:) already registered.', $e->getMessage());
        }

        // Test success
        Http::setAllowOverride(true);
        $this->assertTrue(Http::getAllowOverride());
        Http::get('/')->action(function () {
            echo 'Hello first';
        });

        Http::get('/')->action(function () {
            echo 'Hello second';
        });
    }

    public function testCanHookThrowExceptions()
    {
        $context = clone $this->context;

        $this->http
            ->init()
            ->param('y', '', new Text(5), 'y param', false)
            ->action(function ($y) {
                echo '(init)-' . $y . '-';
            });

        $this->http
            ->shutdown()
            ->action(function () {
                echo '-(shutdown)';
            });

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: ' . $error->getMessage();
            });

        // param not provided for init
        $route = Http::addRoute('GET', '/path-5');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->action(function ($x) {
                echo $x;
            });

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request([]);
                $request->setURI('/path-5');
                $request->setMethod('GET');
                return $request;
            });

        $context
            ->set($request);

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error: Param "y" is not optional.', $result);

        $context = clone $this->context;

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $request = new Request(['y' => 'y-def']);
                $request->setURI('/path-5');
                $request->setMethod('GET');
                return $request;
            });

        $context
            ->set($request);

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('(init)-y-def-x-def-(shutdown)', $result);
    }

    public function providerRouteMatching(): array
    {
        return [
            'GET request' => [Http::REQUEST_METHOD_GET, '/path1'],
            'GET request on different route' => [Http::REQUEST_METHOD_GET, '/path2'],
            'GET request with trailing slash #1' => [Http::REQUEST_METHOD_GET, '/path3', '/path3/'],
            'GET request with trailing slash #2' => [Http::REQUEST_METHOD_GET, '/path3/', '/path3/'],
            'GET request with trailing slash #3' => [Http::REQUEST_METHOD_GET, '/path3/', '/path3'],
            'POST request' => [Http::REQUEST_METHOD_POST, '/path1'],
            'PUT request' => [Http::REQUEST_METHOD_PUT, '/path1'],
            'PATCH request' => [Http::REQUEST_METHOD_PATCH, '/path1'],
            'DELETE request' => [Http::REQUEST_METHOD_DELETE, '/path1'],
            '1 separators' => [Http::REQUEST_METHOD_GET, '/a/'],
            '2 separators' => [Http::REQUEST_METHOD_GET, '/a/b'],
            '3 separators' => [Http::REQUEST_METHOD_GET, '/a/b/c']
        ];
    }

    /**
     * @dataProvider providerRouteMatching
     */
    public function testCanMatchRoute(string $method, string $path, ?string $url = null): void
    {
        $url ??= $path;
        $expected = null;

        switch ($method) {
            case Http::REQUEST_METHOD_GET:
                $expected = Http::get($path);
                break;
            case Http::REQUEST_METHOD_POST:
                $expected = Http::post($path);
                break;
            case Http::REQUEST_METHOD_PUT:
                $expected = Http::put($path);
                break;
            case Http::REQUEST_METHOD_PATCH:
                $expected = Http::patch($path);
                break;
            case Http::REQUEST_METHOD_DELETE:
                $expected = Http::delete($path);
                break;
        }

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $url;

        $route = $this->http->match(new Request());
        $this->assertEquals($expected, $route);
    }

    public function testMatchWithNullPath(): void
    {
        // Create a route for root path
        $expected = Http::get('/');

        // Test case where parse_url returns null (malformed URL)
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '?param=1'; // This will cause parse_url to return null for PATH component

        $matched = $this->http->match(new Request());
        $this->assertEquals($expected, $matched);
    }

    public function testMatchWithEmptyPath(): void
    {
        // Create a route for root path
        $expected = Http::get('/');

        // Test case where URI has no path component
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = 'https://example.com'; // No path component

        $matched = $this->http->match(new Request());
        $this->assertEquals($expected, $matched);
    }

    public function testMatchWithMalformedURL(): void
    {
        // Create a route for root path
        $expected = Http::get('/');

        // Test case where parse_url returns false (severely malformed URL)
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '#fragment'; // Malformed scheme

        $matched = $this->http->match(new Request());
        $this->assertEquals($expected, $matched);
    }

    public function testMatchWithOnlyQueryString(): void
    {
        // Create a route for root path
        $expected = Http::get('/');

        // Test case where URI has only query string (no path)
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '?param=value'; // Only query string, no path

        $matched = $this->http->match(new Request());
        $this->assertEquals($expected, $matched);
    }

    public function testNoMismatchRoute(): void
    {
        $requests = [
            [
                'path' => '/d/:id',
                'url' => '/d/'
            ],
            [
                'path' => '/d/:id/e/:id2',
                'url' => '/d/123/e/'
            ],
            [
                'path' => '/d/:id/e/:id2/f/:id3',
                'url' => '/d/123/e/456/f/'
            ],
        ];

        foreach ($requests as $requestObj) {
            Http::get($requestObj['path']);

            $context = clone $this->context;

            $request = new Dependency();
            $request
                ->setName('request')
                ->setCallback(function () use ($requestObj) {
                    $_SERVER['REQUEST_METHOD'] = Http::REQUEST_METHOD_GET;
                    $_SERVER['REQUEST_URI'] = $requestObj['url'];
                    return new Request();
                });

            $context
                ->set($request);

            $this->http->run($context);

            $this->assertEquals($_SERVER['REQUEST_METHOD'], $context->get('route')->getMethod());
            $this->assertEquals($_SERVER['REQUEST_URI'], $context->get('route')->getPath());
        }
    }

    public function testCanRunRequest(): void
    {
        // Test head requests
        Http::get('/path')
            ->inject('response')
            ->action(function ($response) {
                echo 'HELLO';
            });

        \ob_start();

        $context = clone $this->context;

        $request = new Dependency();
        $request
            ->setName('request')
            ->setCallback(function () {
                $_SERVER['REQUEST_METHOD'] = 'HEAD';
                $_SERVER['REQUEST_URI'] = '/path';
                return new Request();
            });

        $this->context
            ->set($request);

        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertStringNotContainsString('HELLO', $result);
    }

    public function testWildcardRoute(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/unknown_path';

        Http::init()
            ->inject('route')
            ->inject('di')
            ->action(function (Route $route, Container $di) {
                $dependency = new Dependency();
                $dependency->setName('myRoute');
                $dependency->setCallback(fn () => $route);
                $di->set($dependency);
            });

        Http::wildcard()
            ->inject('response')
            ->action(function (Response $response) {
                echo 'HELLO';
            });

        Http::get('/')
            ->inject('response')
            ->action(function (Response $response) {
                $response->send('root /');
            });

        Http::error()
            ->inject('error')
            ->inject('response')
            ->action(function (Throwable $error, Response $response) {
                $response->send($error->getMessage() . ' on file: ' . $error->getFile() . ' on line: ' . $error->getLine());
            });

        $context = clone $this->context;

        \ob_start();
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('HELLO', $result);

        \ob_start();
        $context->get('request')->setMethod('OPTIONS');
        $this->http->run($context);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('', $result);

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
    }
}
