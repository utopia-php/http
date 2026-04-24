<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;

final class RouteMatchTest extends TestCase
{
    protected function setUp(): void
    {
        Http::reset();
    }

    public function testExposesConstructorFields(): void
    {
        $route = new Route('GET', '/users/:id');
        $match = new RouteMatch($route, '/users/42', '/users/:::', '/users/:::');

        $this->assertSame($route, $match->route);
        $this->assertSame('/users/42', $match->urlPath);
        $this->assertSame('/users/:::', $match->routeKey);
        $this->assertSame('/users/:::', $match->preparedPath);
    }

    public function testIsReadonlyClass(): void
    {
        $reflection = new \ReflectionClass(RouteMatch::class);
        $this->assertTrue(
            $reflection->isReadOnly(),
            'RouteMatch must be a readonly class so per-request match facts cannot be mutated by handler code.',
        );
    }

    public function testCannotReassignRouteField(): void
    {
        $route = new Route('GET', '/x');
        $match = new RouteMatch($route, '/x', '/x', '/x');

        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line intentional runtime assertion */
        $match->urlPath = '/mutated';
    }

    public function testWildcardTokenRoundTrips(): void
    {
        $route = new Route('', '');
        $match = new RouteMatch($route, '/anything/at/all', Router::WILDCARD_TOKEN, Router::WILDCARD_TOKEN);

        $this->assertSame(Router::WILDCARD_TOKEN, $match->routeKey);
        $this->assertSame('/anything/at/all', $match->urlPath);
    }
}
