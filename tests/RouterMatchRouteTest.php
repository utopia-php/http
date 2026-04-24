<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;

/**
 * Coverage for {@see Router::matchRoute()} — the coroutine-safe replacement
 * for the legacy Router::match(). These tests focus on the two things that
 * change vs. the legacy API: the return type (RouteMatch), and the
 * no-mutation guarantee on the shared Route definition.
 */
final class RouterMatchRouteTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();
    }

    public function testReturnsNullForUnknownMethod(): void
    {
        $this->assertNull(Router::matchRoute('FROBNICATE', '/anything'));
    }

    public function testReturnsNullForUnmatchedPath(): void
    {
        Router::addRoute(new Route('GET', '/known'));

        $this->assertNull(Router::matchRoute('GET', '/unknown'));
    }

    public function testReturnsRouteMatchForExactPath(): void
    {
        $route = new Route('GET', '/users');
        Router::addRoute($route);

        $match = Router::matchRoute('GET', '/users');

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

        $match = Router::matchRoute('GET', '/users/42');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
        $this->assertSame('/users/42', $match->urlPath);
        $this->assertSame('users/:::', $match->routeKey);
    }

    public function testReturnsRouteMatchForWildcardRoute(): void
    {
        $route = new Route('GET', '/files/*');
        Router::addRoute($route);

        $match = Router::matchRoute('GET', '/files/a/b/c');

        $this->assertNotNull($match);
        $this->assertSame($route, $match->route);
        $this->assertSame('files/*', $match->routeKey);
        $this->assertSame('/files/a/b/c', $match->urlPath);
    }

    public function testReturnsRouteMatchForRootWildcard(): void
    {
        $route = new Route('GET', '/*');
        Router::addRoute($route);

        $match = Router::matchRoute('GET', '/anything');

        $this->assertNotNull($match);
        $this->assertSame(Router::WILDCARD_TOKEN, $match->routeKey);
    }

    public function testDoesNotMutateMatchedRoute(): void
    {
        // The whole reason this PR exists: Router previously wrote
        // $match onto the Route via setMatchedPath(), creating a race
        // between coroutines. Guard against regression.
        $route = new Route('GET', '/users/:id');
        $snapshot = [
            'method' => $route->getMethod(),
            'path' => $route->getPath(),
            'groups' => $route->getGroups(),
            'hook' => $route->getHook(),
        ];

        Router::addRoute($route);

        Router::matchRoute('GET', '/users/1');
        Router::matchRoute('GET', '/users/99');
        Router::matchRoute('GET', '/users/hello');

        $this->assertSame($snapshot['method'], $route->getMethod());
        $this->assertSame($snapshot['path'], $route->getPath());
        $this->assertSame($snapshot['groups'], $route->getGroups());
        $this->assertSame($snapshot['hook'], $route->getHook());
    }

    public function testTwoMatchesReturnDistinctRouteMatchInstances(): void
    {
        $route = new Route('GET', '/users/:id');
        Router::addRoute($route);

        $a = Router::matchRoute('GET', '/users/1');
        $b = Router::matchRoute('GET', '/users/2');

        $this->assertNotNull($a);
        $this->assertNotNull($b);
        $this->assertNotSame($a, $b, 'Each call should produce a fresh RouteMatch value so concurrent handlers cannot observe each other.');
        $this->assertSame($route, $a->route);
        $this->assertSame($route, $b->route);
        $this->assertSame('/users/1', $a->urlPath);
        $this->assertSame('/users/2', $b->urlPath);
    }

    public function testLegacyShimReturnsRoute(): void
    {
        $route = new Route('GET', '/shim');
        Router::addRoute($route);

        // The deprecated match() shim must keep returning the Route
        // directly so external consumers compile during the deprecation
        // window.
        $this->assertSame($route, Router::match('GET', '/shim'));
        $this->assertNull(Router::match('GET', '/missing'));
    }
}
