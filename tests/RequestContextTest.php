<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;

final class RequestContextTest extends TestCase
{
    protected function setUp(): void
    {
        Http::reset();
    }

    public function testFallsThroughToMatchedRouteLabel(): void
    {
        $route = new Route('GET', '/things');
        $route->label('sdk.platform', 'server');

        $match = new RouteMatch($route, '/things', '/things', '/things');
        $context = new RequestContext($match);

        $this->assertSame('server', $context->getLabel('sdk.platform'));
        $this->assertSame('fallback', $context->getLabel('missing', 'fallback'));
    }

    public function testOverridesRouteLabelWithoutMutatingRoute(): void
    {
        $route = new Route('GET', '/things');
        $route->label('router', false);

        $match = new RouteMatch($route, '/things', '/things', '/things');
        $context = new RequestContext($match);

        $context->label('router', true);

        $this->assertTrue($context->getLabel('router'));
        // The shared Route definition is untouched — concurrent requests
        // that don't override see the original value.
        $this->assertFalse($route->getLabel('router', null));
    }

    public function testNullRouteReturnsDefault(): void
    {
        $context = new RequestContext();
        $this->assertSame('default', $context->getLabel('anything', 'default'));
    }

    public function testRouteMatchIsImmutableAtTypeLevel(): void
    {
        $route = new Route('GET', '/x');
        $match = new RouteMatch($route, '/x', '/x', '/x');

        $reflection = new \ReflectionClass($match);
        $this->assertTrue($reflection->isReadOnly(), 'RouteMatch must be a readonly class');
    }
}
