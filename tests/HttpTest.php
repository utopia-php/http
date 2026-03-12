<?php

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\DI\Container;
use Utopia\DI\Dependency;
use Utopia\Http\Tests\UtopiaFPMRequestTest;
use Utopia\Validator\Text;
use Utopia\Http\Adapter\FPM\Request;
use Utopia\Http\Adapter\FPM\Response;
use Utopia\Http\Adapter\FPM\Server;

class HttpTest extends TestCase
{
    protected ?Http $http;

    protected ?Container $container;

    protected ?string $method;

    protected ?string $uri;

    public function setUp(): void
    {
        Http::reset();
        $this->http = new Http(new Server(), 'Asia/Tel_Aviv');
        $this->container = $this->http->getContainer();
        $this->saveRequest();
    }

    public function tearDown(): void
    {
        $this->http = null;
        $this->container = null;
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

    public function testCanInjectContainerInConstructor(): void
    {
        $container = new Container();
        $http = new Http(new Server(), 'Asia/Tel_Aviv', $container);

        $this->assertSame($container, $http->getContainer());
        $this->assertInstanceOf(Container::class, (new Http(new Server(), 'Asia/Tel_Aviv'))->getContainer());
    }

    public function testCanRegisterResourcesWithPublicApi(): void
    {
        $this->http->setResource('rand', fn () => 1234);
        $this->http->setResource('second', fn () => 'second');
        $this->http->setResource('first', fn ($second) => "first-{$second}", ['second']);

        $route = new Route('GET', '/path');

        $route
            ->inject('rand')
            ->inject('first')
            ->action(function ($rand, $first) {
                echo $rand . '-' . $first;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertSame('1234-first-second', $result);
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

    public function testCanGetEnvironmentVariable(): void
    {
        // Mock
        $_SERVER['key'] = 'value';

        $this->assertEquals(Http::getEnv('key'), 'value');
        $this->assertEquals(Http::getEnv('unknown', 'test'), 'test');
    }

    public function testCanGetResources(): void
    {
        $this->container->set('rand', new Dependency([], fn () => rand()));
        $this->container->set('first', new Dependency(['second'], fn ($second) => "first-{$second}"));
        $this->container->set('second', new Dependency([], fn () => 'second'));

        $second = $this->container->get('second');
        $first = $this->container->get('first');
        $this->assertEquals('second', $second);
        $this->assertEquals('first-second', $first);

        $resource = $this->container->get('rand');

        $this->assertNotEmpty($resource);
        $this->assertEquals($resource, $this->container->get('rand'));
        $this->assertEquals($resource, $this->container->get('rand'));
        $this->assertEquals($resource, $this->container->get('rand'));

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->inject('rand')
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->param('y', 'y-def', new Text(200), 'y param', true)
            ->action(function ($x, $y, $rand) {
                echo $x . '-' . $y . '-' . $rand;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def-' . $resource, $result);
    }

    public function testCanGetDefaultValueWithFunction(): void
    {
        $this->container->set('first', new Dependency(['second'], fn ($second) => "first-{$second}"));
        $this->container->set('second', new Dependency([], fn () => 'second'));

        $second = $this->container->get('second');
        $first = $this->container->get('first');
        $this->assertEquals('second', $second);
        $this->assertEquals('first-second', $first);

        // Default Value using function
        $route = new Route('GET', '/path');

        $route
            ->param('x', function ($first, $second) {
                return $first . '-' . $second;
            }, new Text(200), 'x param', true, ['first', 'second'])
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('first-second-second', $result);
    }

    public function testCanExecuteRoute(): void
    {
        $this->container->set('rand', new Dependency([], fn () => rand()));
        $resource = $this->container->get('rand');

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: ' . $error->getMessage();
            });

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->param('y', 'y-def', new Text(200), 'y param', true)
            ->action(function ($x, $y) {
                echo $x . '-' . $y;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        // With Params
        $resource = $this->container->get('rand');
        $route = new Route('GET', '/path');

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
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y', 'z' => 'param-z']);
        $this->http->execute($route, $request, '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals($resource . '-param-x-param-y', $result);

        // With Error
        $resource = $this->container->get('rand');
        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(1, min: 0), 'x param', false)
            ->param('y', 'y-def', new Text(1, min: 0), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '-', $y;
            });

        \ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->http->execute($route, $request, '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error: Invalid `x` param: Value must be a valid string and no longer than 1 chars', $result);

        // With Hooks
        $resource = $this->container->get('rand');
        $this->http
            ->init()
            ->inject('rand')
            ->action(function ($rand) {
                echo 'init-' . $rand . '-';
            });

        $this->http
            ->shutdown()
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

        $route = new Route('GET', '/path');

        $route
            ->groups(['api'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '-', $y;
            });

        $homepage = new Route('GET', '/path');

        $homepage
            ->groups(['homepage'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '*', $y;
            });

        \ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->http->execute($route, $request, '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-' . $resource . '-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        $resource = $this->container->get('rand');
        \ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->http->execute($homepage, $request, '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-' . $resource . '-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
    }

    public function testCanAddAndExecuteHooks()
    {
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
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('(init)-x-def-(shutdown)', $result);

        // Default Params
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->hook(false)
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
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
        $this->http
            ->init()
            ->param('y', '', new Text(5), 'y param', false)
            ->action(function ($y) {
                echo '(init)-' . $y . '-';
            });

        $this->http
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error-' . $error->getMessage();
            });

        $this->http
            ->shutdown()
            ->action(function () {
                echo '-(shutdown)';
            });

        // param not provided for init
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error-Param "y" is not optional.', $result);

        \ob_start();
        $_GET['y'] = 'y-def';
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('(init)-y-def-x-def-(shutdown)', $result);
    }

    public function testCanSetRoute()
    {
        $route = new Route('GET', '/path');

        $this->assertEquals($this->http->getRoute(), null);
        $this->http->setRoute($route);
        $this->assertEquals($this->http->getRoute(), $route);
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

        $this->assertEquals($expected, $this->http->match(new Request()));
        $this->assertEquals($expected, $this->http->getRoute());
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

        foreach ($requests as $request) {
            Http::get($request['path']);

            $_SERVER['REQUEST_METHOD'] = Http::REQUEST_METHOD_GET;
            $_SERVER['REQUEST_URI'] = $request['url'];

            $route = $this->http->match(new Request(), fresh: true);

            $this->assertEquals(null, $route);
            $this->assertEquals(null, $this->http->getRoute());
        }
    }

    public function testCanMatchFreshRoute(): void
    {
        $route1 = Http::get('/path1');
        $route2 = Http::get('/path2');

        try {
            // Match first request
            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $_SERVER['REQUEST_URI'] = '/path1';
            $matched = $this->http->match(new Request());
            $this->assertEquals($route1, $matched);
            $this->assertEquals($route1, $this->http->getRoute());

            // Second request match returns cached route
            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $_SERVER['REQUEST_URI'] = '/path2';
            $request2 = new Request();
            $matched = $this->http->match($request2, fresh: false);
            $this->assertEquals($route1, $matched);
            $this->assertEquals($route1, $this->http->getRoute());

            // Fresh match returns new route
            $matched = $this->http->match($request2, fresh: true);
            $this->assertEquals($route2, $matched);
            $this->assertEquals($route2, $this->http->getRoute());
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testCanRunRequest(): void
    {
        // Test head requests

        $method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : null;
        $uri = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;

        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_SERVER['REQUEST_URI'] = '/path';

        Http::get('/path')
            ->inject('response')
            ->action(function ($response) {
                $response->send('HELLO');
            });

        \ob_start();
        $this->http->run(new Request(), new Response(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertStringNotContainsString('HELLO', $result);
    }

    public function testScopedResourcesUseNestedContainers(): void
    {
        $counter = 0;
        $http = new class (new Server(), 'Asia/Tel_Aviv') extends Http {
            public function createScope(?Container $scope = null): Container
            {
                return ($scope ?? $this->container)->scope();
            }

            public function defineResource(string $name, callable $callback, array $injections = [], ?Container $scope = null): void
            {
                $this->setResource($name, $callback, $injections, $scope);
            }

            public function resource(string $name, Container $scope): mixed
            {
                return $this->getResource($name, $scope);
            }
        };

        $http->defineResource('shared', function () use (&$counter) {
            $counter++;

            return $counter;
        });

        $requestA = $http->createScope();
        $requestB = $http->createScope();
        $executionA = $http->createScope($requestA);

        $http->defineResource('requestId', fn () => 'request-a', scope: $requestA);
        $http->defineResource('requestId', fn () => 'request-b', scope: $requestB);
        $http->defineResource('requestId', fn () => 'execution-a', scope: $executionA);

        $this->assertSame(1, $http->resource('shared', $requestA));
        $this->assertSame(1, $http->resource('shared', $requestB));
        $this->assertSame('request-a', $http->resource('requestId', $requestA));
        $this->assertSame('request-b', $http->resource('requestId', $requestB));
        $this->assertSame('execution-a', $http->resource('requestId', $executionA));
        $this->assertSame('request-a', $http->resource('requestId', $requestA));
    }

    public function testWildcardRoute(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/unknown_path';

        Http::init()
            ->action(function () {
                $route = $this->http->getRoute();
                $this->container->set('myRoute', new Dependency([], fn () => $route));
            });


        Http::wildcard()
            ->inject('myRoute')
            ->inject('response')
            ->action(function (mixed $myRoute, $response) {
                if ($myRoute == null) {
                    $response->send('ROUTE IS NULL!');
                } else {
                    $response->send('HELLO');
                }
            });

        \ob_start();
        @$this->http->run(new Request(), new Response(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('HELLO', $result);

        \ob_start();
        $req = new Request();
        $req = $req->setMethod('OPTIONS');
        @$this->http->run($req, new Response(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('', $result);

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
    }

    public function testCallableStringParametersNotExecuted(): void
    {
        // Test that callable strings (like function names) are not executed
        $route = new Route('GET', '/test-callable-string');

        $route
            ->param('callback', 'phpinfo', new Text(200), 'callback param', true)
            ->action(function ($callback) {
                // If the string 'phpinfo' was executed as a function,
                // it would output PHP info. Instead, it should just be the string.
                echo 'callback-value: ' . $callback;
            });

        \ob_start();
        $this->http->execute($route, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('callback-value: phpinfo', $result);

        // Test with request parameter that is a callable string
        $route2 = new Route('GET', '/test-callable-string-param');

        $route2
            ->param('func', 'default', new Text(200), 'func param', false)
            ->action(function ($func) {
                echo 'func-value: ' . $func;
            });

        \ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['func' => 'system']);
        $this->http->execute($route2, $request, '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('func-value: system', $result);

        // Test callable closure still works
        $route3 = new Route('GET', '/test-callable-closure');

        $route3
            ->param('generated', function () {
                return 'generated-value';
            }, new Text(200), 'generated param', true)
            ->action(function ($generated) {
                echo 'generated: ' . $generated;
            });

        \ob_start();
        $this->http->execute($route3, new Request(), '1');
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('generated: generated-value', $result);
    }
}
