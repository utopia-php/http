<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\Http\Adapter\FPM\Request;

/**
 * Coverage for {@see Router::matchRequest()} — the sole public matching
 * entry point after the coroutine-safety refactor. Focus areas: the
 * return-type shape ({@see RouteMatch}), the no-mutation guarantee on
 * shared Route definitions, and the method-agnostic wildcard fallback
 * via {@see Router::setWildcard()}.
 */
final class RouterMatchRouteTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();
    }

    private function match(string $method, string $uri): ?RouteMatch
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        return Router::matchRequest(new Request());
    }

    public function testReturnsNullForUnknownMethodWithoutWildcard(): void
    {
        $this->assertNull($this->match('FROBNICATE', '/anything'));
    }

    public function testReturnsNullForUnmatchedPath(): void
    {
        Router::addRoute(new Route('GET', '/known'));

        $this->assertNull($this->match('GET', '/unknown'));
    }

    public function testReturnsRouteMatchForExactPath(): void
    {
        $route = new Route('GET', '/users');
        Router::addRoute($route);

        $match = $this->match('GET', '/users');

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame($route, $match->route);
        $this->assertSame('/users', $match->urlPath);
        $this->assertSame('users', $match->routeKey);
        $this->assertSame('users', $match->preparedPath);
    }

    public function testReturnsRouteMatchForParameterizedPath(): void
    {
        $route = new Route('GET', '/users/:id');
        Router::addRoute($route);

        $match = $this->match('GET', '/users/42');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
        $this->assertSame('/users/42', $match->urlPath);
        $this->assertSame('users/:::', $match->routeKey);
    }

    public function testReturnsRouteMatchForPrefixWildcardRoute(): void
    {
        $route = new Route('GET', '/files/*');
        Router::addRoute($route);

        $match = $this->match('GET', '/files/a/b/c');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
        $this->assertSame('files/*', $match->routeKey);
        $this->assertSame('/files/a/b/c', $match->urlPath);
    }

    public function testReturnsRouteMatchForMethodSpecificRootWildcard(): void
    {
        $route = new Route('GET', '/*');
        Router::addRoute($route);

        $match = $this->match('GET', '/anything');

        $this->assertNotNull($match);
        $this->assertSame(Router::WILDCARD_TOKEN, $match->routeKey);
    }

    public function testMethodAgnosticWildcardCatchesUnknownPath(): void
    {
        $wildcard = new Route('', '');
        Router::setWildcard($wildcard);
        Router::addRoute(new Route('GET', '/known'));

        $match = $this->match('GET', '/definitely-unknown');

        $this->assertNotNull($match);
        $this->assertSame($wildcard, $match->route);
        $this->assertSame(Router::WILDCARD_TOKEN, $match->routeKey);
        $this->assertSame('/definitely-unknown', $match->urlPath);
    }

    public function testMethodAgnosticWildcardCatchesUnknownMethod(): void
    {
        // Method-agnostic wildcard fires even when the HTTP method isn't
        // one of the registered buckets (GET/POST/PUT/PATCH/DELETE).
        $wildcard = new Route('', '');
        Router::setWildcard($wildcard);

        $match = $this->match('FROBNICATE', '/anything');

        $this->assertNotNull($match);
        $this->assertSame($wildcard, $match->route);
    }

    public function testMethodSpecificMatchTakesPrecedenceOverWildcard(): void
    {
        $specific = new Route('GET', '/users');
        Router::addRoute($specific);
        Router::setWildcard(new Route('', ''));

        $match = $this->match('GET', '/users');

        $this->assertNotNull($match);
        $this->assertSame($specific, $match->route, 'A method-specific route must win over the wildcard fallback.');
    }

    public function testMatchRequestExtractsPathFromUri(): void
    {
        $route = new Route('GET', '/users/:id');
        Router::addRoute($route);

        $match = $this->match('GET', '/users/42?extra=ignored');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
        $this->assertSame('/users/42', $match->urlPath);
    }

    public function testMatchRequestDefaultsEmptyPathToSlash(): void
    {
        $route = new Route('GET', '/');
        Router::addRoute($route);

        $match = $this->match('GET', 'https://example.com?x=1');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
        $this->assertSame('/', $match->urlPath);
    }

    public function testMatchRequestNormalisesHeadToGet(): void
    {
        $route = new Route('GET', '/head-target');
        Router::addRoute($route);

        $match = $this->match('HEAD', '/head-target');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
    }

    public function testDoesNotMutateMatchedRoute(): void
    {
        // Regression guard: the router previously wrote matched facts back
        // onto the Route via setMatchedPath(), creating a race between
        // coroutines. Repeated matches must leave the Route byte-identical.
        $route = new Route('GET', '/users/:id');
        $snapshot = [
            'method' => $route->getMethod(),
            'path' => $route->getPath(),
            'groups' => $route->getGroups(),
            'hook' => $route->getHook(),
        ];

        Router::addRoute($route);

        $this->match('GET', '/users/1');
        $this->match('GET', '/users/99');
        $this->match('GET', '/users/hello');

        $this->assertSame($snapshot['method'], $route->getMethod());
        $this->assertSame($snapshot['path'], $route->getPath());
        $this->assertSame($snapshot['groups'], $route->getGroups());
        $this->assertSame($snapshot['hook'], $route->getHook());
    }

    public function testTwoMatchesReturnDistinctRouteMatchInstances(): void
    {
        $route = new Route('GET', '/users/:id');
        Router::addRoute($route);

        $a = $this->match('GET', '/users/1');
        $b = $this->match('GET', '/users/2');

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertNotSame($a, $b, 'Each call should produce a fresh RouteMatch value so concurrent handlers cannot observe each other.');
        $this->assertSame($route, $a->route);
        $this->assertSame($route, $b->route);
        $this->assertSame('/users/1', $a->urlPath);
        $this->assertSame('/users/2', $b->urlPath);
    }
}
