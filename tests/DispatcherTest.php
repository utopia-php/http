<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\DI\Container;
use Utopia\Http\Adapter\FPM\Request;
use Utopia\Http\Adapter\FPM\Response;
use Utopia\Http\Adapter\FPM\Server;
use Utopia\Validator\Text;

/**
 * End-to-end coverage for {@see Dispatcher} via Http::run().
 *
 * Focus: the per-request isolation guarantees that motivate this refactor.
 * We use the FPM adapter because it's synchronous — every concurrency
 * regression in the per-request state machine shows up as a sequential
 * cross-request leak here.
 */
final class DispatcherTest extends TestCase
{
    private Http $http;

    private ?string $savedMethod;

    private ?string $savedUri;

    protected function setUp(): void
    {
        Http::reset();
        $container = new Container();
        $this->http = new Http(new Server($container), 'UTC');
        $this->savedMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->savedUri = $_SERVER['REQUEST_URI'] ?? null;
    }

    protected function tearDown(): void
    {
        $_SERVER['REQUEST_METHOD'] = $this->savedMethod;
        $_SERVER['REQUEST_URI'] = $this->savedUri;
    }

    /**
     * @param callable(): void $block
     */
    private function capture(callable $block): string
    {
        \ob_start();
        $block();
        $output = \ob_get_contents() ?: '';
        \ob_end_clean();

        return $output;
    }

    private function runRequest(string $method, string $uri): string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return $this->capture(function () {
            $this->http->run(new Request(), new Response());
        });
    }

    public function testRegistersRouteAndRouteMatchRequestResources(): void
    {
        $route = Http::get('/resources-check')
            ->inject('route')
            ->inject('routeMatch')
            ->inject('response')
            ->action(function (?Route $route, ?RouteMatch $match, Response $response) {
                $payload = \json_encode([
                    'routeClass' => $route === null ? null : $route::class,
                    'matchClass' => $match === null ? null : $match::class,
                    'matchUrl' => $match?->urlPath,
                    'matchKey' => $match?->routeKey,
                ]);
                $response->send($payload === false ? '' : $payload);
            });

        $output = $this->runRequest('GET', '/resources-check');

        $decoded = \json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertSame(Route::class, $decoded['routeClass']);
        $this->assertSame(RouteMatch::class, $decoded['matchClass']);
        $this->assertSame('/resources-check', $decoded['matchUrl']);
        $this->assertSame('resources-check', $decoded['matchKey']);
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testWildcardRouteIsNeverMutated(): void
    {
        $wildcard = Http::wildcard()
            ->inject('routeMatch')
            ->inject('response')
            ->action(function (RouteMatch $match, Response $response) {
                $response->send($match->urlPath);
            });

        $pathBefore = $wildcard->getPath();
        $groupsBefore = $wildcard->getGroups();

        $first = $this->runRequest('GET', '/alpha/beta');
        $second = $this->runRequest('GET', '/something/else/entirely');

        // The dispatcher must not write the request path back onto the
        // shared wildcard Route definition — that was the concurrency bug.
        $this->assertSame($pathBefore, $wildcard->getPath(), 'Wildcard Route::getPath() must not be mutated by dispatch.');
        $this->assertSame($groupsBefore, $wildcard->getGroups());

        // But the match exposed to the handler must still reflect the
        // current request URL.
        $this->assertSame('/alpha/beta', $first);
        $this->assertSame('/something/else/entirely', $second);
    }

    public function testSequentialRequestsOnSameParameterizedRouteDoNotBleed(): void
    {
        Http::get('/users/:id')
            ->inject('routeMatch')
            ->inject('request')
            ->inject('response')
            ->action(function (RouteMatch $match, Request $request, Response $response) {
                $response->send($match->urlPath . '|' . $match->route->getPathValues($request, $match->preparedPath)['id']);
            });

        $a = $this->runRequest('GET', '/users/42');
        $b = $this->runRequest('GET', '/users/99');

        $this->assertSame('/users/42|42', $a);
        $this->assertSame('/users/99|99', $b);
    }

    public function testInitAndShutdownHooksFire(): void
    {
        Http::init()->action(function () {
            echo 'init|';
        });
        Http::shutdown()->action(function () {
            echo '|shutdown';
        });

        Http::get('/lifecycle')
            ->inject('response')
            ->action(function (Response $response) {
                echo 'handler';
                $response->send('');
            });

        $output = $this->runRequest('GET', '/lifecycle');

        $this->assertSame('init|handler|shutdown', $output);
    }

    public function testErrorHookFiresForNotFound(): void
    {
        Http::error()
            ->inject('error')
            ->inject('response')
            ->action(function (\Throwable $error, Response $response) {
                $response->send('err:' . $error->getCode() . ':' . $error->getMessage());
            });

        $output = $this->runRequest('GET', '/definitely-not-registered');

        $this->assertSame('err:404:Not Found', $output);
    }

    public function testErrorHookReceivesExceptionFromHandler(): void
    {
        Http::get('/boom')
            ->action(function () {
                throw new Exception('kaboom', 418);
            });

        Http::error()
            ->inject('error')
            ->inject('response')
            ->action(function (\Throwable $error, Response $response) {
                $response->send($error->getCode() . ':' . $error->getMessage());
            });

        $output = $this->runRequest('GET', '/boom');

        $this->assertSame('418:kaboom', $output);
    }

    public function testHeadRequestResolvesToGetRouteWithPayloadDisabled(): void
    {
        Http::get('/head-check')
            ->inject('response')
            ->action(function (Response $response) {
                $response->send('body-should-not-appear');
            });

        $output = $this->runRequest('HEAD', '/head-check');

        $this->assertStringNotContainsString('body-should-not-appear', $output);
    }

    public function testOptionsHookFiresForOptionsMethod(): void
    {
        Http::get('/opts')->action(function () {
            // never called
            echo 'GET-HANDLER';
        });

        Http::options()
            ->inject('response')
            ->action(function (Response $response) {
                $response->send('OPTIONS-HANDLER');
            });

        $output = $this->runRequest('OPTIONS', '/opts');

        $this->assertSame('OPTIONS-HANDLER', $output);
        $this->assertStringNotContainsString('GET-HANDLER', $output);
    }

    public function testInitHookMutationsToRequestParamsAreVisibleToRouteAction(): void
    {
        // Regression: Dispatcher::execute must re-read $request->getParams()
        // at each hook/action call site. Hoisting the array into a local
        // before init hooks fire would cache a pre-hook snapshot, so the
        // route action would see stale params despite an init hook having
        // mutated them (e.g. to apply auth/filter rewrites).
        Http::init()
            ->inject('request')
            ->action(function (Request $request) {
                $request->setQueryString(['x' => 'from-init-hook']);
            });

        Http::get('/filter-me')
            ->param('x', 'original', new Text(64), 'x param', true)
            ->inject('response')
            ->action(function (string $x, Response $response) {
                $response->send($x);
            });

        $output = $this->runRequest('GET', '/filter-me');

        $this->assertSame('from-init-hook', $output);
    }

    public function testShutdownHookSeesMutationsFromInitHook(): void
    {
        // The same guarantee for the init → shutdown path: shutdown hooks
        // read getArguments() fresh, so an init-time mutation is visible.
        Http::init()
            ->inject('request')
            ->action(function (Request $request) {
                $request->setQueryString(['token' => 'init-token']);
            });

        Http::shutdown()
            ->param('token', '', new Text(64), 'token param', true)
            ->inject('response')
            ->action(function (string $token, Response $response) {
                echo '|shutdown:' . $token;
            });

        Http::get('/lifecycle-params')
            ->inject('response')
            ->action(function (Response $response) {
                $response->send('ok');
            });

        $output = $this->runRequest('GET', '/lifecycle-params');

        $this->assertStringContainsString('ok', $output);
        $this->assertStringContainsString('|shutdown:init-token', $output);
    }

    public function testWildcardRouteMatchCarriesWildcardToken(): void
    {
        Http::wildcard()
            ->inject('routeMatch')
            ->inject('response')
            ->action(function (RouteMatch $match, Response $response) {
                $response->send($match->routeKey);
            });

        $output = $this->runRequest('GET', '/whatever/this/is');

        $this->assertSame(Router::WILDCARD_TOKEN, $output);
    }
}
