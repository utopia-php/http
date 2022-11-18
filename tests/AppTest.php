<?php

namespace Utopia;

use PHPUnit\Framework\TestCase;
use Utopia\Tests\UtopiaRequestTest;
use Utopia\Validator\Text;

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
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y, $rand) {
                echo $x.'-'.$y.'-'.$rand;
            });

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def-'.$resource, $result);
    }

    public function testCanAddRoute(): void
    {
        $getRoute = App::addRoute(App::REQUEST_METHOD_GET, '/addroute');
        $postRoute = App::addRoute(App::REQUEST_METHOD_POST, '/addroute');

        $routes = App::getRoutes();
        $this->assertEquals($getRoute, $routes[App::REQUEST_METHOD_GET]['/addroute']);
        $this->assertEquals($postRoute, $routes[App::REQUEST_METHOD_POST]['/addroute']);

        $this->expectExceptionMessage('Invalid Request Method');
        App::addRoute('REST', '/addroute');
    }

    public function testCanExecuteRoute(): void
    {
        App::setResource('rand', fn () => rand());
        $resource = $this->app->getResource('rand');

        $this->app
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error: '.$error->getMessage();
            });

        // Default Params
        $route = new Route('GET', '/path');

        $route
            ->alias('/path1', ['x' => 'x-def-1', 'y' => 'y-def-1'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y) {
                echo $x.'-'.$y;
            });

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        // test alias with param override
        $route->setIsAlias(true);

        \ob_start();
        $this->app->execute($route, new Request());
        $result1 = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('x-def-y-def', $result);
        $this->assertEquals('x-def-1-y-def-1', $result1);

        // With Params

        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->inject('rand')
            ->param('z', 'z-def', function ($rand) {
                echo $rand.'-';

                return new Text(200);
            }, 'z param', false, ['rand'])
            ->action(function ($x, $y, $z, $rand) {
                echo $x.'-', $y;
            });

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals($resource.'-param-x-param-y', $result);

        // With Error

        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(1), 'x param', false)
            ->param('y', 'y-def', new Text(1), 'y param', false)
            ->action(function ($x, $y) {
                echo $x.'-', $y;
            });

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error: Invalid x: Value must be a valid string and no longer than 1 chars', $result);

        // With Hooks

        $this->app
            ->init()
            ->inject('rand')
            ->action(function ($rand) {
                echo 'init-'.$rand.'-';
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
                echo $x.'-', $y;
            });

        $homepage = new Route('GET', '/path');

        $homepage
            ->groups(['homepage'])
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->param('y', 'y-def', new Text(200), 'y param', false)
            ->action(function ($x, $y) {
                echo $x.'*', $y;
            });

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($route, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-'.$resource.'-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        \ob_start();
        $request = new UtopiaRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->app->execute($homepage, $request);
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('init-'.$resource.'-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
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
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        // var_dump($result);
        $this->assertEquals('(init)-x-def-(shutdown)', $result);

        // Default Params
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->hook(false)
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        // var_dump($result);
        $this->assertEquals('x-def', $result);
    }

    public function testCanHookThrowExceptions()
    {
        $this->app
            ->init()
            ->param('y', '', new Text(5), 'y param', false)
            ->action(function ($y) {
                echo '(init)-'.$y.'-';
            });

        $this->app
            ->error()
            ->inject('error')
            ->action(function ($error) {
                echo 'error-'.$error->getMessage();
            });

        $this->app
            ->shutdown()
            ->action(function () {
                echo '-(shutdown)';
            });

        // param not provided for init
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', false)
            ->action(function ($x) {
                echo $x;
            });

        \ob_start();
        $this->app->execute($route, new Request());
        $result = \ob_get_contents();
        \ob_end_clean();

        $this->assertEquals('error-Param "y" is not optional.', $result);

        \ob_start();
        $_GET['y'] = 'y-def';
        $this->app->execute($route, new Request());
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
        $route1 = App::get('/path1');
        $route2 = App::get('/path2');
        $route3 = App::post('/path1');
        $route4 = App::put('/path1');
        $route5 = App::patch('/path1');
        $route6 = App::delete('/path1');
        $route7 = App::get('/a/b/c');
        $route8 = App::get('/a/b');
        $route9 = App::get('/a');

        return [
            'GET request' => [$route1, App::REQUEST_METHOD_GET, '/path1'],
            'GET request on different route' => [$route2, App::REQUEST_METHOD_GET, '/path2'],
            'POST request' => [$route3, App::REQUEST_METHOD_POST, '/path1'],
            'PUT request' => [$route4, App::REQUEST_METHOD_PUT, '/path1'],
            'PATCH request' => [$route5, App::REQUEST_METHOD_PATCH, '/path1'],
            'DELETE request' => [$route6, App::REQUEST_METHOD_DELETE, '/path1'],
            // "/a/b/c" needs to be first
            '3 separators' => [$route7, App::REQUEST_METHOD_GET, '/a/b/c'],
            '2 separators' => [$route8, App::REQUEST_METHOD_GET, '/a/b'],
            '1 separators' => [$route9, App::REQUEST_METHOD_GET, '/a'],
        ];
    }

    /**
     * @dataProvider providerRouteMatching
     */
    public function testCanMatchRoute(Route $expected, string $method, string $path): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;

        $this->assertEquals($expected, $this->app->match(new Request()));
        $this->assertEquals($expected, $this->app->getRoute());
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

    public function testCanRunAliasEndpoint(): void
    {
        // Test head requests
        App::get('/storage/buckets/:bucketId/files/:fileId')
            ->alias('/storage/files/:fileId', [
                'bucketId' => 'default',
            ])
            ->param('bucketId', 'bucketid', new Text(100), 'My id', false)
            ->param('fileId', 'fileId', new Text(100), 'My id', false)
            ->inject('response')
            ->action(function ($bucketId, $fileId, $response) {
                $response->send('HELLO');
            });

        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_SERVER['REQUEST_URI'] = '/storage/files/myfileid';

        // Test Alias
        \ob_start();
        $this->app->run(new Request(), new Response());
        $result1 = \ob_get_contents();
        \ob_end_clean();

        $this->assertStringNotContainsString('HELLO', $result1);
    }
}
