<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Utopia;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RouterStressTest extends TestCase
{
    public static function stressSizes(): array
    {
        return [
            [2_000],
            [5_000],
            [10_000],
            [15_000],
            [20_000],
        ];
    }

    public function tearDown(): void
    {
        Router::setAllowOverride(false);
        Router::reset();
    }

    /**
     * @dataProvider stressSizes
     */
    public function testStressStaticAndDynamicRoutes(int $count): void
    {
        $staticRoutes = [];
        $dynamicRoutes = [];

        for ($i = 0; $i < $count; $i++) {
            $staticKey = "s$i";
            $dynamicKey = "d$i";
            $static = new Route(App::REQUEST_METHOD_GET, "/static/$staticKey");
            Router::addRoute($static);
            $staticRoutes[$staticKey] = $static;

            $dynamic = new Route(App::REQUEST_METHOD_GET, "/dyn/$dynamicKey/:id");
            Router::addRoute($dynamic);
            $dynamicRoutes[$dynamicKey] = $dynamic;
        }

        $wildcard = new Route(App::REQUEST_METHOD_GET, '/wild/*');
        Router::addRoute($wildcard);

        for ($i = 0; $i < $count; $i++) {
            $staticKey = "s$i";
            $matched = Router::match(App::REQUEST_METHOD_GET, "/static/$staticKey");
            $this->assertSame($staticRoutes[$staticKey], $matched);
            $this->assertEquals("static/$staticKey", $matched->getMatchedPath());
        }

        for ($i = 0; $i < $count; $i++) {
            $dynamicKey = "d$i";
            $matched = Router::match(App::REQUEST_METHOD_GET, "/dyn/$dynamicKey/value");
            $this->assertSame($dynamicRoutes[$dynamicKey], $matched);
            $this->assertEquals("dyn/$dynamicKey/:::", $matched->getMatchedPath());
        }

        $matched = Router::match(App::REQUEST_METHOD_GET, '/wild/anything/else');
        $this->assertSame($wildcard, $matched);
        $this->assertEquals('wild/*', $matched->getMatchedPath());

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();
        $this->assertLessThanOrEqual(Router::ROUTE_MATCH_CACHE_LIMIT, count($cache));
    }

    public function testStressDeepDynamicRoutes(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/a/:b/c/:d/e/:f/g/:h/i/:j/k/:l/m/:n');
        Router::addRoute($route);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/a/1/c/2/e/3/g/4/i/5/k/6/m/7');
        $this->assertSame($route, $matched);
        $this->assertEquals('a/:::/c/:::/e/:::/g/:::/i/:::/k/:::/m/:::', $matched->getMatchedPath());
    }

    public function testStressCacheEviction(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route);

        $total = Router::ROUTE_MATCH_CACHE_LIMIT + 100;
        for ($i = 0; $i < $total; $i++) {
            Router::match(App::REQUEST_METHOD_GET, "/users/$i");
        }

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();

        $this->assertCount(Router::ROUTE_MATCH_CACHE_LIMIT, $cache);
        $this->assertArrayHasKey('GET:/users/' . ($total - 1), $cache);
    }
}
