<?php

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace Utopia;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RouterTrieTest extends TestCase
{
    public function tearDown(): void
    {
        Router::reset();
    }

    public function testTrieExactMatchPriority(): void
    {
        $routeParam = new Route(App::REQUEST_METHOD_GET, '/users/:userId');
        $routeExact = new Route(App::REQUEST_METHOD_GET, '/users/me');

        Router::addRoute($routeParam);
        Router::addRoute($routeExact);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/me');
        $this->assertEquals($routeExact, $matched);
        $this->assertEquals('/users/me', $matched->getPath());

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $this->assertEquals($routeParam, $matched);
        $this->assertEquals('users/:::', $matched->getMatchedPath());
    }

    public function testTrieDeepNesting(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/a/:b/c/:d/e/:f/g/:h');

        Router::addRoute($route);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/a/1/c/2/e/3/g/4');
        $this->assertNotNull($matched);
        $this->assertEquals($route, $matched);
        $this->assertEquals('a/:::/c/:::/e/:::/g/:::', $matched->getMatchedPath());
    }

    public function testTrieMultipleConsecutiveParams(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/api/:version/:resource/:id');

        Router::addRoute($route);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/api/v1/users/123');
        $this->assertNotNull($matched);
        $this->assertEquals($route, $matched);
        $this->assertEquals('api/:::/:::/:::', $matched->getMatchedPath());
    }

    public function testTriePartialPathRejection(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id/posts');

        Router::addRoute($route);

        $this->assertNull(Router::match(App::REQUEST_METHOD_GET, '/users/123'));
        $this->assertNull(Router::match(App::REQUEST_METHOD_GET, '/users'));

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123/posts');
        $this->assertNotNull($matched);
        $this->assertEquals($route, $matched);
    }

    public function testTrieCacheLimitBounded(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route);

        for ($i = 0; $i < 11000; $i++) {
            Router::match(App::REQUEST_METHOD_GET, "/users/$i");
        }

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();

        $this->assertLessThanOrEqual(10000, count($cache));
    }

    public function testCacheInvalidationOnRouteAdd(): void
    {
        $route1 = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route1);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $this->assertEquals($route1, $matched);

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cacheBefore = $property->getValue();
        $this->assertGreaterThan(0, count($cacheBefore));

        $route2 = new Route(App::REQUEST_METHOD_GET, '/posts/:id');
        Router::addRoute($route2);

        $cacheAfter = $property->getValue();
        $this->assertCount(0, $cacheAfter);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $this->assertEquals($route1, $matched);
    }

    public function testNegativeResultCaching(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route);

        $this->assertNull(Router::match(App::REQUEST_METHOD_GET, '/nonexistent'));

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();

        $this->assertArrayHasKey('GET:/nonexistent', $cache);
        $this->assertFalse($cache['GET:/nonexistent']);
    }

    public function testAliasWithDifferentParamStructure(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/v1/databases/:databaseId/collections');
        Router::addRoute($route);
        Router::addRouteAlias('/v1/db/:databaseId/collections', $route);

        $matched1 = Router::match(App::REQUEST_METHOD_GET, '/v1/databases/mydb/collections');
        $this->assertEquals($route, $matched1);
        $this->assertEquals('v1/databases/:::/collections', $matched1->getMatchedPath());

        $matched2 = Router::match(App::REQUEST_METHOD_GET, '/v1/db/mydb/collections');
        $this->assertEquals($route, $matched2);
        $this->assertEquals('v1/db/:::/collections', $matched2->getMatchedPath());
    }

    public function testMethodIsolationInCache(): void
    {
        $getRoute = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        $postRoute = new Route(App::REQUEST_METHOD_POST, '/users/:id');

        Router::addRoute($getRoute);
        Router::addRoute($postRoute);

        $matchedGet = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $matchedPost = Router::match(App::REQUEST_METHOD_POST, '/users/123');

        $this->assertEquals($getRoute, $matchedGet);
        $this->assertEquals($postRoute, $matchedPost);

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();

        $this->assertArrayHasKey('GET:/users/123', $cache);
        $this->assertArrayHasKey('POST:/users/123', $cache);
    }

    public function testWildcardAndTrieInteraction(): void
    {
        $wildcardRoute = new Route(App::REQUEST_METHOD_GET, '/api/*');
        $trieRoute = new Route(App::REQUEST_METHOD_GET, '/api/users/:id');

        Router::addRoute($wildcardRoute);
        Router::addRoute($trieRoute);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/api/users/123');
        $this->assertEquals($trieRoute, $matched);
        $this->assertEquals('api/users/:::', $matched->getMatchedPath());

        $matched = Router::match(App::REQUEST_METHOD_GET, '/api/something/else');
        $this->assertEquals($wildcardRoute, $matched);
        $this->assertEquals('api/*', $matched->getMatchedPath());
    }

    public function testEmptyAndRootPathHandling(): void
    {
        $rootRoute = new Route(App::REQUEST_METHOD_GET, '/');

        Router::addRoute($rootRoute);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/');
        $this->assertEquals($rootRoute, $matched);

        $matched = Router::match(App::REQUEST_METHOD_GET, '');
        $this->assertEquals($rootRoute, $matched);
    }

    public function testMixedStaticAndParamsInPath(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/api/v1/:resource/items/:id/details');

        Router::addRoute($route);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/api/v1/users/items/123/details');
        $this->assertNotNull($matched);
        $this->assertEquals($route, $matched);
        $this->assertEquals('api/v1/:::/items/:::/details', $matched->getMatchedPath());
    }

    public function testLRUCachePromotion(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route);

        Router::match(App::REQUEST_METHOD_GET, '/users/1');
        Router::match(App::REQUEST_METHOD_GET, '/users/2');
        Router::match(App::REQUEST_METHOD_GET, '/users/3');

        Router::match(App::REQUEST_METHOD_GET, '/users/1');

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();

        $keys = array_keys($cache);
        $this->assertEquals('GET:/users/1', end($keys));
    }

    public function testPatternStorageCorrectness(): void
    {
        $route1 = new Route(App::REQUEST_METHOD_GET, '/users/:userId/posts/:postId');
        $route2 = new Route(App::REQUEST_METHOD_GET, '/users/:id');

        Router::addRoute($route1);
        Router::addRoute($route2);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123/posts/456');
        $this->assertEquals($route1, $matched);
        $this->assertEquals('users/:::/posts/:::', $matched->getMatchedPath());

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $this->assertEquals($route2, $matched);
        $this->assertEquals('users/:::', $matched->getMatchedPath());
    }

    public function testVeryLongPath(): void
    {
        $route = new Route(
            App::REQUEST_METHOD_GET,
            '/a/:b/c/:d/e/:f/g/:h/i/:j/k/:l/m/:n/o/:p/q/:r/s/:t'
        );

        Router::addRoute($route);

        $matched = Router::match(
            App::REQUEST_METHOD_GET,
            '/a/1/c/2/e/3/g/4/i/5/k/6/m/7/o/8/q/9/s/10'
        );
        $this->assertNotNull($matched);
        $this->assertEquals($route, $matched);
        $this->assertEquals('a/:::/c/:::/e/:::/g/:::/i/:::/k/:::/m/:::/o/:::/q/:::/s/:::', $matched->getMatchedPath());
    }

    public function testTrailingSlashNormalization(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');

        Router::addRoute($route);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123/');
        $this->assertNotNull($matched);
        $this->assertEquals($route, $matched);
    }

    public function testMultipleAliasesForSameRoute(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/v1/databases/:databaseId');
        Router::addRoute($route);
        Router::addRouteAlias('/v1/db/:databaseId', $route);
        Router::addRouteAlias('/v1/database/:databaseId', $route);

        $matched1 = Router::match(App::REQUEST_METHOD_GET, '/v1/databases/test');
        $matched2 = Router::match(App::REQUEST_METHOD_GET, '/v1/db/test');
        $matched3 = Router::match(App::REQUEST_METHOD_GET, '/v1/database/test');

        $this->assertEquals($route, $matched1);
        $this->assertEquals($route, $matched2);
        $this->assertEquals($route, $matched3);

        $this->assertContains($matched1->getMatchedPath(), ['v1/databases/:::', 'v1/db/:::', 'v1/database/:::']);
        $this->assertContains($matched2->getMatchedPath(), ['v1/databases/:::', 'v1/db/:::', 'v1/database/:::']);
        $this->assertContains($matched3->getMatchedPath(), ['v1/databases/:::', 'v1/db/:::', 'v1/database/:::']);
    }

    public function testAllowOverrideInvalidatesCacheAndReplacesRoute(): void
    {
        Router::setAllowOverride(true);

        $route1 = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route1);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $this->assertEquals($route1, $matched);

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cacheBefore = $property->getValue();
        $this->assertGreaterThan(0, count($cacheBefore));

        $route2 = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route2);

        $cacheAfter = $property->getValue();
        $this->assertEquals(0, count($cacheAfter));

        $matched = Router::match(App::REQUEST_METHOD_GET, '/users/123');
        $this->assertEquals($route2, $matched);
        $this->assertNotEquals($route1, $matched);

        Router::setAllowOverride(false);
    }

    public function testMethodValidationOnAlias(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method (INVALID) not supported.');

        $invalidRoute = new Route('INVALID', '/users/:id');
        Router::addRouteAlias('/alias/:id', $invalidRoute);
    }

    public function testWildcardPrecedenceRootVsPathSpecific(): void
    {
        $rootWildcard = new Route(App::REQUEST_METHOD_GET, '*');
        $pathWildcard = new Route(App::REQUEST_METHOD_GET, '/api/*');

        Router::addRoute($rootWildcard);
        Router::addRoute($pathWildcard);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/api/something');
        $this->assertEquals($pathWildcard, $matched);
        $this->assertEquals('api/*', $matched->getMatchedPath());

        $matched = Router::match(App::REQUEST_METHOD_GET, '/other/path');
        $this->assertEquals($rootWildcard, $matched);
        $this->assertEquals('*', $matched->getMatchedPath());
    }

    public function testNegativeResultInvalidatedByLaterRouteAdd(): void
    {
        $route1 = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route1);

        $this->assertNull(Router::match(App::REQUEST_METHOD_GET, '/posts/123'));

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();
        $this->assertArrayHasKey('GET:/posts/123', $cache);
        $this->assertFalse($cache['GET:/posts/123']);

        $route2 = new Route(App::REQUEST_METHOD_GET, '/posts/:id');
        Router::addRoute($route2);

        $cacheAfter = $property->getValue();
        $this->assertEquals(0, count($cacheAfter));

        $matched = Router::match(App::REQUEST_METHOD_GET, '/posts/123');
        $this->assertEquals($route2, $matched);
    }

    public function testTrieWinsOverWildcardWithTrailingSlash(): void
    {
        $wildcardRoute = new Route(App::REQUEST_METHOD_GET, '/api/*');
        $trieRoute = new Route(App::REQUEST_METHOD_GET, '/api/users/:id');

        Router::addRoute($wildcardRoute);
        Router::addRoute($trieRoute);

        $matched = Router::match(App::REQUEST_METHOD_GET, '/api/users/123/');
        $this->assertEquals($trieRoute, $matched);
        $this->assertEquals('api/users/:::', $matched->getMatchedPath());
    }

    public function testParamExtractionCorrectnessForAliases(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/v1/databases/:databaseId/collections/:collectionId');
        Router::addRoute($route);
        Router::addRouteAlias('/v1/db/:dbId/col/:colId', $route);

        $matched1 = Router::match(App::REQUEST_METHOD_GET, '/v1/databases/mydb/collections/mycol');
        $this->assertEquals($route, $matched1);
        $this->assertEquals('v1/databases/:::/collections/:::', $matched1->getMatchedPath());

        $reflection = new ReflectionClass(Route::class);
        $property = $reflection->getProperty('pathParams');
        $pathParams = $property->getValue($matched1);

        $this->assertArrayHasKey('v1/databases/:::/collections/:::', $pathParams);
        $this->assertArrayHasKey('databaseId', $pathParams['v1/databases/:::/collections/:::']);
        $this->assertArrayHasKey('collectionId', $pathParams['v1/databases/:::/collections/:::']);
        $this->assertEquals(2, $pathParams['v1/databases/:::/collections/:::']['databaseId']);
        $this->assertEquals(4, $pathParams['v1/databases/:::/collections/:::']['collectionId']);

        $matched2 = Router::match(App::REQUEST_METHOD_GET, '/v1/db/testdb/col/testcol');
        $this->assertEquals($route, $matched2);
        $this->assertEquals('v1/db/:::/col/:::', $matched2->getMatchedPath());

        $this->assertArrayHasKey('v1/db/:::/col/:::', $pathParams);
        $this->assertArrayHasKey('dbId', $pathParams['v1/db/:::/col/:::']);
        $this->assertArrayHasKey('colId', $pathParams['v1/db/:::/col/:::']);
        $this->assertEquals(2, $pathParams['v1/db/:::/col/:::']['dbId']);
        $this->assertEquals(4, $pathParams['v1/db/:::/col/:::']['colId']);
    }

    public function testActualParamExtractionViaGetPathValues(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:userId/posts/:postId');
        Router::addRoute($route);
        Router::addRouteAlias('/u/:uid/p/:pid', $route);

        $matched1 = Router::match(App::REQUEST_METHOD_GET, '/users/123/posts/456');
        $this->assertEquals($route, $matched1);

        $request1 = new Request();
        $request1->setURI('/users/123/posts/456');
        $params1 = $matched1->getPathValues($request1);
        $this->assertArrayHasKey('userId', $params1);
        $this->assertArrayHasKey('postId', $params1);
        $this->assertEquals('123', $params1['userId']);
        $this->assertEquals('456', $params1['postId']);

        $matched2 = Router::match(App::REQUEST_METHOD_GET, '/u/abc/p/xyz');
        $this->assertEquals($route, $matched2);

        $request2 = new Request();
        $request2->setURI('/u/abc/p/xyz');
        $params2 = $matched2->getPathValues($request2, $matched2->getMatchedPath());
        $this->assertArrayHasKey('uid', $params2);
        $this->assertArrayHasKey('pid', $params2);
        $this->assertEquals('abc', $params2['uid']);
        $this->assertEquals('xyz', $params2['pid']);
    }

    public function testLRUEvictionOrder(): void
    {
        $route = new Route(App::REQUEST_METHOD_GET, '/users/:id');
        Router::addRoute($route);

        for ($i = 0; $i < Router::ROUTE_MATCH_CACHE_LIMIT + 5; $i++) {
            Router::match(App::REQUEST_METHOD_GET, "/users/$i");
        }

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('matchCache');
        $cache = $property->getValue();

        $this->assertCount(Router::ROUTE_MATCH_CACHE_LIMIT, $cache);

        $keys = array_keys($cache);
        $firstKey = $keys[0];
        $lastKey = end($keys);

        $this->assertEquals('GET:/users/5', $firstKey);
        $this->assertEquals('GET:/users/' . (Router::ROUTE_MATCH_CACHE_LIMIT + 4), $lastKey);

        Router::match(App::REQUEST_METHOD_GET, '/users/5');

        $cacheAfterPromotion = $property->getValue();
        $keysAfterPromotion = array_keys($cacheAfterPromotion);
        $lastKeyAfterPromotion = end($keysAfterPromotion);

        $this->assertEquals('GET:/users/5', $lastKeyAfterPromotion);
    }

    public function testTrieStatsReflectStructure(): void
    {
        $routes = [
            '/api/v1/users/:id',
            '/api/v1/posts/:id',
            '/api/v2/users/:id/comments/:commentId',
            '/api/v2/posts/:postId/likes/:likeId',
            '/api/v3/search/:query',
        ];

        foreach ($routes as $path) {
            Router::addRoute(new Route(App::REQUEST_METHOD_GET, $path));
        }

        for ($i = 1; $i <= 10; $i++) {
            Router::addRoute(new Route(App::REQUEST_METHOD_POST, "/data/batch/$i/:itemId"));
        }

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('tries');
        $tries = $property->getValue();

        $this->assertArrayHasKey(App::REQUEST_METHOD_GET, $tries);
        $this->assertArrayHasKey(App::REQUEST_METHOD_POST, $tries);

        $stats = $tries[App::REQUEST_METHOD_GET]->getStats();

        $this->assertArrayHasKey('total_nodes', $stats);
        $this->assertArrayHasKey('max_depth', $stats);
        $this->assertArrayHasKey('total_routes', $stats);

        $this->assertEquals(5, $stats['total_routes']);
        $this->assertGreaterThan(0, $stats['total_nodes']);
        $this->assertGreaterThanOrEqual(5, $stats['max_depth']);

        $postStats = $tries[App::REQUEST_METHOD_POST]->getStats();
        $this->assertEquals(10, $postStats['total_routes']);
        $this->assertGreaterThan(0, $postStats['total_nodes']);
    }

    public function testInsertWithNullPatternThrowsException(): void
    {
        $this->expectException(\TypeError::class);

        $trie = new RouterTrie();
        $route = new Route(App::REQUEST_METHOD_GET, '/test');

        $trie->insert(['test'], $route, null);
    }
}
