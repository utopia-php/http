<?php

namespace Utopia;

use PHPUnit\Framework\TestCase;
use Utopia\Tests\UtopiaRequestTest;
use Utopia\Validator\Text;
use Exception;

class AppTest extends TestCase
{
    protected ?App $app;

    protected ?string $method;

    protected ?string $uri;

    public function setUp(): void
    {
        App::reset();
        $this->app = new App('Asia/Tel_Aviv');
        $this->saveRequest();
    }

    public function tearDown(): void
    {
        $this->app = null;
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
        $this->assertEmpty(App::getMode());
        $this->assertFalse(App::isProduction());
        $this->assertFalse(App::isDevelopment());
        $this->assertFalse(App::isStage());

        App::setMode(App::MODE_TYPE_PRODUCTION);

        $this->assertEquals(App::MODE_TYPE_PRODUCTION, App::getMode());
        $this->assertTrue(App::isProduction());
        $this->assertFalse(App::isDevelopment());
        $this->assertFalse(App::isStage());

        App::setMode(App::MODE_TYPE_DEVELOPMENT);

        $this->assertEquals(App::MODE_TYPE_DEVELOPMENT, App::getMode());
        $this->assertFalse(App::isProduction());
        $this->assertTrue(App::isDevelopment());
        $this->assertFalse(App::isStage());

        App::setMode(App::MODE_TYPE_STAGE);

        $this->assertEquals(App::MODE_TYPE_STAGE, App::getMode());
        $this->assertFalse(App::isProduction());
        $this->assertFalse(App::isDevelopment());
        $this->assertTrue(App::isStage());
    }

    public function testCanGetEnvironmentVariable(): void
    {
        // Mock
        $_SERVER['key'] = 'value';

        $this->assertEquals(App::getEnv('key'), 'value');
        $this->assertEquals(App::getEnv('unknown', 'test'), 'test');
    }

    public function testCanGetResources(): void
    {
        App::setResource('rand', fn () => rand());
        App::setResource('first', fn ($second) => "first-{$second}", ['second']);
        App::setResource('second', fn () => 'second');

        $second = $this->app->getResource('second');
        $first = $this->app->getResource('first');
        $this->assertEquals('second', $second);
        $this->assertEquals('first-second', $first);

        $resource = $this->app->getResource('rand');

        $this->assertNotEmpty($resource);
        $this->assertEquals($resource, $this->app->getResource('rand'));
        $this->assertEquals($resource, $this->app->getResource('rand'));
        $this->assertEquals($resource, $this->app->getResource('rand'));

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
        $this->app->execute($route, new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def-' . $resource, $result);
    }

    public function testCanExecuteRoute(): void
    {
        App::setResource('rand', fn () => rand());
        $resource = $this->app->getResource('rand');

        $this->app
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
        $this->app->execute($route, new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        // With Params

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
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y', 'z' => 'param-z']);
        $this->app->execute($route, $request, new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals($resource . '-param-x-param-y', $result);

        // With Error

        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(1, min: 0), 'x param', false)
            ->param('y', 'y-def', new Text(1, min: 0), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '-', $y;
            });

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request, new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error: Invalid `x` param: Value must be a valid string and no longer than 1 chars', $result);

        // With Hooks

        $this->app
            ->init()
            ->inject('rand')
            ->action(function ($rand) {
                echo 'init-' . $rand . '-';
            });

        $this->app
            ->shutdown()
            ->action(function () {
                echo '-shutdown';
            });

        $this->app
            ->init()
            ->groups(['api'])
            ->action(function () {
                echo '(init-api)-';
            });

        $this->app
            ->shutdown()
            ->groups(['api'])
            ->action(function () {
                echo '-(shutdown-api)';
            });

        $this->app
            ->init()
            ->groups(['homepage'])
            ->action(function () {
                echo '(init-homepage)-';
            });

        $this->app
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
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request, new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-' . $resource . '-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($homepage, $request, new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-' . $resource . '-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
    }

    public function testCanAddAndExecuteHooks()
    {
        $this->app
            ->init()
            ->action(function () {
                echo '(init)-';
            });

        $this->app
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
        $this->app->execute($route, new Request(), new Response());
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
        $this->app->execute($route, new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def', $result);
    }

    public function testAllowRouteOverrides()
    {
        App::setAllowOverride(false);
        $this->assertFalse(App::getAllowOverride());
        App::get('/')->action(function () {
            echo 'Hello first';
        });

        try {
            App::get('/')->action(function () {
                echo 'Hello second';
            });
            $this->fail('Failed to throw exception');
        } catch (Exception $e) {
            // Threw exception as expected
            $this->assertEquals('Route for (GET:) already registered.', $e->getMessage());
        }

        // Test success
        App::setAllowOverride(true);
        $this->assertTrue(App::getAllowOverride());
        App::get('/')->action(function () {
            echo 'Hello first';
        });

        App::get('/')->action(function () {
            echo 'Hello second';
        });
    }

    public function testCanHookThrowExceptions()
    {
        $this->app
            ->init()
            ->param('y', '', new Text(5), 'y param', false)
            ->action(function ($y) {
                echo '(init)-' . $y . '-';
            });

        $this->app
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error-' . $error->getMessage();
            });

        $this->app
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
        $this->app->execute($route, new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error-Param "y" is not optional.', $result);

        \ob_start();
        $_GET['y'] = 'y-def';
        $this->app->execute($route, new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('(init)-y-def-x-def-(shutdown)', $result);
    }

    public function testCanSetRoute()
    {
        $route = new Route('GET', '/path');

        $this->assertEquals($this->app->getRoute(), null);
        $this->app->setRoute($route);
        $this->assertEquals($this->app->getRoute(), $route);
    }

    public function providerRouteMatching(): array
    {
        return [
            'GET request' => [App::REQUEST_METHOD_GET, '/path1'],
            'GET request on different route' => [App::REQUEST_METHOD_GET, '/path2'],
            'GET request with trailing slash #1' => [App::REQUEST_METHOD_GET, '/path3', '/path3/'],
            'GET request with trailing slash #2' => [App::REQUEST_METHOD_GET, '/path3/', '/path3/'],
            'GET request with trailing slash #3' => [App::REQUEST_METHOD_GET, '/path3/', '/path3'],
            'POST request' => [App::REQUEST_METHOD_POST, '/path1'],
            'PUT request' => [App::REQUEST_METHOD_PUT, '/path1'],
            'PATCH request' => [App::REQUEST_METHOD_PATCH, '/path1'],
            'DELETE request' => [App::REQUEST_METHOD_DELETE, '/path1'],
            '1 separators' => [App::REQUEST_METHOD_GET, '/a/'],
            '2 separators' => [App::REQUEST_METHOD_GET, '/a/b'],
            '3 separators' => [App::REQUEST_METHOD_GET, '/a/b/c']
        ];
    }

    /**
     * @dataProvider providerRouteMatching
     */
    public function testCanMatchRoute(string $method, string $path, string $url = null): void
    {
        $url ??= $path;
        $expected = null;

        switch ($method) {
            case App::REQUEST_METHOD_GET:
                $expected = App::get($path);
                break;
            case App::REQUEST_METHOD_POST:
                $expected = App::post($path);
                break;
            case App::REQUEST_METHOD_PUT:
                $expected = App::put($path);
                break;
            case App::REQUEST_METHOD_PATCH:
                $expected = App::patch($path);
                break;
            case App::REQUEST_METHOD_DELETE:
                $expected = App::delete($path);
                break;
        }

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $url;

        $this->assertEquals($expected, $this->app->match(new Request()));
        $this->assertEquals($expected, $this->app->getRoute());
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
            App::get($request['path']);

            $_SERVER['REQUEST_METHOD'] = App::REQUEST_METHOD_GET;
            $_SERVER['REQUEST_URI'] = $request['url'];

            $route = $this->app->match(new Request(), fresh: true);

            $this->assertEquals(null, $route);
            $this->assertEquals(null, $this->app->getRoute());
        }
    }

    public function testCanMatchFreshRoute(): void
    {
        $route1 = App::get('/path1');
        $route2 = App::get('/path2');

        try {
            // Match first request
            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $_SERVER['REQUEST_URI'] = '/path1';
            $matched = $this->app->match(new Request());
            $this->assertEquals($route1, $matched);
            $this->assertEquals($route1, $this->app->getRoute());

            // Second request match returns cached route
            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $_SERVER['REQUEST_URI'] = '/path2';
            $request2 = new Request();
            $matched = $this->app->match($request2);
            $this->assertEquals($route1, $matched);
            $this->assertEquals($route1, $this->app->getRoute());

            // Fresh match returns new route
            $matched = $this->app->match($request2, fresh: true);
            $this->assertEquals($route2, $matched);
            $this->assertEquals($route2, $this->app->getRoute());
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

        App::get('/path')
            ->inject('response')
            ->action(function ($response) {
                $response->send('HELLO');
            });

        \ob_start();
        $this->app->run(new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertStringNotContainsString('HELLO', $result);
    }

    public function testWildcardRoute(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/unknown_path';

        App::init()
            ->inject('request')
            ->inject('response')
            ->action(function (Request $request, Response $response) {
                $route = $this->app->getRoute();
                App::setResource('myRoute', fn () => $route);

                if ($request->getURI() === '/init_response') {
                    $response->send('THIS IS RESPONSE FROM INIT!');
                }
            });

        App::options()
            ->inject('request')
            ->inject('response')
            ->action(function (Request $request, Response $response) {
                $origin = $request->getOrigin();
                $response
                    ->addHeader('Server', 'Appwrite')
                    ->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE')
                    ->addHeader('Access-Control-Allow-Headers', 'Origin, Cookie, Set-Cookie, X-Requested-With, Content-Type, Access-Control-Allow-Origin, Access-Control-Request-Headers, Accept, X-Appwrite-Project, X-Appwrite-Key, X-Appwrite-Locale, X-Appwrite-Mode, X-Appwrite-JWT, X-Appwrite-Response-Format, X-SDK-Version, X-SDK-Name, X-SDK-Language, X-SDK-Platform, X-Appwrite-ID, Content-Range, Range, Cache-Control, Expires, Pragma, X-Fallback-Cookies')
                    ->addHeader('Access-Control-Expose-Headers', 'X-Fallback-Cookies')
                    ->addHeader('Access-Control-Allow-Origin', $origin)
                    ->addHeader('Access-Control-Allow-Credentials', 'true')
                    ->noContent();
            });

        App::wildcard()
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
        @$this->app->run(new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('HELLO', $result);

        \ob_start();
        $req = new Request();
        $req = $req->setMethod('OPTIONS');
        @$this->app->run($req, new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('', $result);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/init_response';

        \ob_start();
        @$this->app->run(new Request(), new Response());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('THIS IS RESPONSE FROM INIT!', $result);

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
    }
}
