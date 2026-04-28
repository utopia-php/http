<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Utopia\DI\Container;
use Utopia\Http\Adapter\FPM\Request;
use Utopia\Http\Adapter\FPM\Response;
use Utopia\Http\Adapter\FPM\Server;
use Utopia\Http\Tests\UtopiaFPMRequestTest;
use Utopia\Validator\Text;

final class HttpTest extends TestCase
{
    protected ?Http $http;

    protected ?Container $container;

    protected ?string $method;

    protected ?string $uri;

    public function setUp(): void
    {
        Http::reset();
        $this->container = new Container();
        $this->http = new Http(new Server($this->container), 'Asia/Tel_Aviv');
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

    public function testCanGetDifferentModes(): void
    {
        $this->assertEmpty(Http::getMode());
        $this->assertFalse(Http::isProduction());
        $this->assertFalse(Http::isDevelopment());
        $this->assertFalse(Http::isStage());

        Http::setMode(Http::MODE_TYPE_PRODUCTION);

        $this->assertSame(Http::MODE_TYPE_PRODUCTION, Http::getMode());
        $this->assertTrue(Http::isProduction());
        $this->assertFalse(Http::isDevelopment());
        $this->assertFalse(Http::isStage());

        Http::setMode(Http::MODE_TYPE_DEVELOPMENT);

        $this->assertSame(Http::MODE_TYPE_DEVELOPMENT, Http::getMode());
        $this->assertFalse(Http::isProduction());
        $this->assertTrue(Http::isDevelopment());
        $this->assertFalse(Http::isStage());

        Http::setMode(Http::MODE_TYPE_STAGE);

        $this->assertSame(Http::MODE_TYPE_STAGE, Http::getMode());
        $this->assertFalse(Http::isProduction());
        $this->assertFalse(Http::isDevelopment());
        $this->assertTrue(Http::isStage());
    }

    public function testCanGetEnvironmentVariable(): void
    {
        // Mock
        $_SERVER['key'] = 'value';

        $this->assertSame('value', Http::getEnv('key'));
        $this->assertSame('test', Http::getEnv('unknown', 'test'));
    }

    public function testCanExecuteRoute(): void
    {
        $this->container->set('rand', fn() => rand());
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

        ob_start();
        $this->http->execute($route, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

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

        ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y', 'z' => 'param-z']);
        $this->http->execute($route, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame($resource . '-param-x-param-y', $result);

        // With Error
        $resource = $this->container->get('rand');
        $route = new Route('GET', '/path');

        $route
            ->param('x', 'x-def', new Text(1, min: 0), 'x param', false)
            ->param('y', 'y-def', new Text(1, min: 0), 'y param', false)
            ->action(function ($x, $y) {
                echo $x . '-', $y;
            });

        ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->http->execute($route, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('error: Invalid `x` param: Value must be a valid string and no longer than 1 chars', $result);

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

        ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->http->execute($route, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('init-' . $resource . '-(init-api)-param-x-param-y-(shutdown-api)-shutdown', $result);

        $resource = $this->container->get('rand');
        ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['x' => 'param-x', 'y' => 'param-y']);
        $this->http->execute($homepage, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('init-' . $resource . '-(init-homepage)-param-x*param-y-(shutdown-homepage)-shutdown', $result);
    }

    public function testCanAddAndExecuteHooks(): void
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

        ob_start();
        $this->http->execute($route, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('(init)-x-def-(shutdown)', $result);

        // Default Params
        $route = new Route('GET', '/path');
        $route
            ->param('x', 'x-def', new Text(200), 'x param', true)
            ->hook(false)
            ->action(function ($x) {
                echo $x;
            });

        ob_start();
        $this->http->execute($route, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('x-def', $result);
    }

    public function testAllowRouteOverrides(): void
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
            $this->assertSame('Route for (GET:) already registered.', $e->getMessage());
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

    public function testCanHookThrowExceptions(): void
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

        ob_start();
        $this->http->execute($route, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('error-Param "y" is not optional.', $result);

        ob_start();
        $_GET['y'] = 'y-def';
        $this->http->execute($route, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('(init)-y-def-x-def-(shutdown)', $result);
    }

    public function testShutdownHookCanInjectResolvedArguments(): void
    {
        $route = new Route('GET', '/files/:fileId');
        $route
            ->param('fileId', '', new Text(64), 'file id', false)
            ->param('width', 0, new Text(8), 'width', true)
            ->action(function ($fileId, $width) {
                echo 'action:' . $fileId . ',' . $width;
            });

        $this->http
            ->shutdown()
            ->inject('arguments')
            ->action(function (array $arguments) {
                echo '|shutdown:fileId=' . $arguments['fileId'] . ',width=' . $arguments['width'];
            });

        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['fileId' => 'abc123', 'width' => '200']);
        $_SERVER['REQUEST_URI'] = '/files/abc123';

        ob_start();
        $this->http->execute($route, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('action:abc123,200|shutdown:fileId=abc123,width=200', $result);
    }

    /**
     * @return \Iterator<string, array<int, string>>
     */
    public static function providerRouteMatching(): \Iterator
    {
        yield 'GET request' => [Http::REQUEST_METHOD_GET, '/path1'];
        yield 'GET request on different route' => [Http::REQUEST_METHOD_GET, '/path2'];
        yield 'GET request with trailing slash #1' => [Http::REQUEST_METHOD_GET, '/path3', '/path3/'];
        yield 'GET request with trailing slash #2' => [Http::REQUEST_METHOD_GET, '/path3/', '/path3/'];
        yield 'GET request with trailing slash #3' => [Http::REQUEST_METHOD_GET, '/path3/', '/path3'];
        yield 'POST request' => [Http::REQUEST_METHOD_POST, '/path1'];
        yield 'PUT request' => [Http::REQUEST_METHOD_PUT, '/path1'];
        yield 'PATCH request' => [Http::REQUEST_METHOD_PATCH, '/path1'];
        yield 'DELETE request' => [Http::REQUEST_METHOD_DELETE, '/path1'];
        yield '1 separators' => [Http::REQUEST_METHOD_GET, '/a/'];
        yield '2 separators' => [Http::REQUEST_METHOD_GET, '/a/b'];
        yield '3 separators' => [Http::REQUEST_METHOD_GET, '/a/b/c'];
    }

    #[DataProvider('providerRouteMatching')]
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

        $this->assertSame($expected, $this->http->match(new Request()));
        $this->assertSame($expected, $this->http->getResource('route'));
    }

    public function testNoMismatchRoute(): void
    {
        $requests = [
            [
                'path' => '/d/:id',
                'url' => '/d/',
            ],
            [
                'path' => '/d/:id/e/:id2',
                'url' => '/d/123/e/',
            ],
            [
                'path' => '/d/:id/e/:id2/f/:id3',
                'url' => '/d/123/e/456/f/',
            ],
        ];

        foreach ($requests as $request) {
            Http::get($request['path']);

            $_SERVER['REQUEST_METHOD'] = Http::REQUEST_METHOD_GET;
            $_SERVER['REQUEST_URI'] = $request['url'];

            $route = $this->http->match(new Request(), fresh: true);

            $this->assertNull($route);
            $this->assertNull($this->http->getResource('route'));
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
            $this->assertSame($route1, $matched);
            $this->assertSame($route1, $this->http->getResource('route'));

            // Second request match returns cached route
            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $_SERVER['REQUEST_URI'] = '/path2';
            $request2 = new Request();
            $matched = $this->http->match($request2, fresh: false);
            $this->assertSame($route1, $matched);
            $this->assertSame($route1, $this->http->getResource('route'));

            // Fresh match returns new route
            $matched = $this->http->match($request2, fresh: true);
            $this->assertSame($route2, $matched);
            $this->assertSame($route2, $this->http->getResource('route'));
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testCanMatchRootRouteWhenUriHasNoPath(): void
    {
        $route = Http::get('/');

        $_SERVER['REQUEST_METHOD'] = Http::REQUEST_METHOD_GET;
        $_SERVER['REQUEST_URI'] = 'https://example.com?x=1';

        $this->assertSame($route, $this->http->match(new Request()));
        $this->assertSame($route, $this->http->getResource('route'));
    }

    public function testCanRunRequest(): void
    {
        // Test head requests

        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $_SERVER['REQUEST_URI'] = '/path';

        Http::get('/path')
            ->inject('response')
            ->action(function ($response) {
                $response->send('HELLO');
            });

        ob_start();
        $this->http->run(new Request(), new Response());
        $result = ob_get_contents() ?: '';
        ob_end_clean();

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

        Http::init()
            ->inject('route')
            ->action(function ($route) {
                $this->container->set('myRoute', fn() => $route);
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

        ob_start();
        @$this->http->run(new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('HELLO', $result);

        ob_start();
        $req = new Request();
        $req = $req->setMethod('OPTIONS');
        @$this->http->run($req, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('', $result);

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
    }

    public function testWildcardRouteWhenUriHasNoPath(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = 'https://example.com?x=1';

        Http::wildcard()
            ->inject('response')
            ->action(function ($response) {
                $response->send('HELLO');
            });

        ob_start();
        @$this->http->run(new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        $this->assertSame('HELLO', $result);
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

        ob_start();
        $this->http->execute($route, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('callback-value: phpinfo', $result);

        // Test with request parameter that is a callable string
        $route2 = new Route('GET', '/test-callable-string-param');

        $route2
            ->param('func', 'default', new Text(200), 'func param', false)
            ->action(function ($func) {
                echo 'func-value: ' . $func;
            });

        ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['func' => 'system']);
        $this->http->execute($route2, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('func-value: system', $result);

        // Test callable closure still works
        $route3 = new Route('GET', '/test-callable-closure');

        $route3
            ->param('generated', fn() => 'generated-value', new Text(200), 'generated param', true)
            ->action(function ($generated) {
                echo 'generated: ' . $generated;
            });

        ob_start();
        $this->http->execute($route3, new Request(), new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertSame('generated: generated-value', $result);
    }

    public function testCanInjectResourceAndParamWithSameName(): void
    {
        // Register a 'locale' resource returning a Locale instance whose
        // `name` statically resolves to "en".
        $this->container->set('locale', fn() => new Locale());

        $route = new Route('GET', '/path');

        $route
            ->param('locale', 'en-default', new Text(10), 'locale param', false)
            ->inject('locale')
            ->action(function (string $localeParam, Locale $localeResource) {
                echo json_encode([
                    'localeParam' => $localeParam,
                    'localeResource' => $localeResource->name,
                ]);
            });

        ob_start();
        $request = new UtopiaFPMRequestTest();
        $request::_setParams(['locale' => 'es']);
        $this->http->execute($route, $request, new Response());
        $result = ob_get_contents();
        ob_end_clean();

        $expected = json_encode([
            'localeParam' => 'es',
            'localeResource' => 'en',
        ]);

        $this->assertEquals($expected, $result);
    }
}

/**
 * Dummy Locale class used by testCanInjectResourceAndParamWithSameName to
 * verify resource injection alongside a same-named request parameter.
 */
class Locale
{
    public string $name = 'en';
}
