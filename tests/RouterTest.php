<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function setUp(): void
    {
        Router::setAllowOverride(false);
    }

    public function tearDown(): void
    {
        Router::reset();
    }

    public function testCanMatchUrl(): void
    {
        $routeIndex = new Route(Http::REQUEST_METHOD_GET, '/');
        $routeAbout = new Route(Http::REQUEST_METHOD_GET, '/about');
        $routeAboutMe = new Route(Http::REQUEST_METHOD_GET, '/about/me');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutMe);

        $this->assertEquals($routeIndex, Router::match(Http::REQUEST_METHOD_GET, '/')?->route);
        $this->assertEquals($routeAbout, Router::match(Http::REQUEST_METHOD_GET, '/about')?->route);
        $this->assertEquals($routeAboutMe, Router::match(Http::REQUEST_METHOD_GET, '/about/me')?->route);
    }

    public function testCanMatchUrlWithPlaceholder(): void
    {
        $routeBlog = new Route(Http::REQUEST_METHOD_GET, '/blog');
        $routeBlogAuthors = new Route(Http::REQUEST_METHOD_GET, '/blog/authors');
        $routeBlogAuthorsComments = new Route(Http::REQUEST_METHOD_GET, '/blog/authors/comments');
        $routeBlogPost = new Route(Http::REQUEST_METHOD_GET, '/blog/:post');
        $routeBlogPostComments = new Route(Http::REQUEST_METHOD_GET, '/blog/:post/comments');
        $routeBlogPostCommentsSingle = new Route(Http::REQUEST_METHOD_GET, '/blog/:post/comments/:comment');

        Router::addRoute($routeBlog);
        Router::addRoute($routeBlogAuthors);
        Router::addRoute($routeBlogAuthorsComments);
        Router::addRoute($routeBlogPost);
        Router::addRoute($routeBlogPostComments);
        Router::addRoute($routeBlogPostCommentsSingle);

        $this->assertEquals($routeBlog, Router::match(Http::REQUEST_METHOD_GET, '/blog')?->route);
        $this->assertEquals($routeBlogAuthors, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors')?->route);
        $this->assertEquals($routeBlogAuthorsComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors/comments')?->route);
        $this->assertEquals($routeBlogPost, Router::match(Http::REQUEST_METHOD_GET, '/blog/test')?->route);
        $this->assertEquals($routeBlogPostComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments')?->route);
        $this->assertEquals($routeBlogPostCommentsSingle, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments/:comment')?->route);
    }

    public function testCanMatchUrlWithWildcard(): void
    {
        $routeIndex = new Route('GET', '/');
        $routeAbout = new Route('GET', '/about');
        $routeAboutWildcard = new Route('GET', '/about/*');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutWildcard);

        $this->assertEquals($routeIndex, Router::match('GET', '/')?->route);
        $this->assertEquals($routeAbout, Router::match('GET', '/about')?->route);
        $this->assertEquals($routeAboutWildcard, Router::match('GET', '/about/me')?->route);
        $this->assertEquals($routeAboutWildcard, Router::match('GET', '/about/you')?->route);
        $this->assertEquals($routeAboutWildcard, Router::match('GET', '/about/me/myself/i')?->route);
    }

    public function testCanMatchHttpMethod(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/');
        $routePOST = new Route(Http::REQUEST_METHOD_POST, '/');

        Router::addRoute($routeGET);
        Router::addRoute($routePOST);

        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/')?->route);
        $this->assertEquals($routePOST, Router::match(Http::REQUEST_METHOD_POST, '/')?->route);

        $this->assertNotEquals($routeGET, Router::match(Http::REQUEST_METHOD_POST, '/')?->route);
        $this->assertNotEquals($routePOST, Router::match(Http::REQUEST_METHOD_GET, '/')?->route);
    }

    public function testCanMatchAlias(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias')
            ->alias('/alias2');

        Router::addRoute($routeGET);

        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2')?->route);
    }

    public function testCanMatchMultipleAliases(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias1')
            ->alias('/alias2')
            ->alias('/alias3');

        Router::addRoute($routeGET);

        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias1')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias3')?->route);
    }

    public function testCanMatchMix(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/');
        $routeGET
            ->alias('/console/*')
            ->alias('/auth/*')
            ->alias('/invite')
            ->alias('/login')
            ->alias('/recover')
            ->alias('/register/*');

        Router::addRoute($routeGET);

        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/invite')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/login')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/recover')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console/lorem/ipsum/dolor')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/auth/lorem/ipsum')?->route);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/register/lorem/ipsum')?->route);
    }

    public function testCanMatchMethodAlias(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/userinfo');
        $route->aliasMethod(Http::REQUEST_METHOD_POST);

        Router::addRoute($route);

        $this->assertEquals($route, Router::match(Http::REQUEST_METHOD_GET, '/userinfo')?->route);
        $this->assertEquals($route, Router::match(Http::REQUEST_METHOD_POST, '/userinfo')?->route);
        $this->assertNull(Router::match(Http::REQUEST_METHOD_PUT, '/userinfo'));

        $this->assertSame(Http::REQUEST_METHOD_GET, $route->getMethod());
        $this->assertSame([Http::REQUEST_METHOD_POST], $route->getAliasMethods());
    }

    public function testCanMatchMethodAliasWithPlaceholder(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/users/:id');
        $route->aliasMethod(Http::REQUEST_METHOD_POST);

        Router::addRoute($route);

        $match = Router::match(Http::REQUEST_METHOD_POST, '/users/abc-123');

        $this->assertEquals($route, $match?->route);
        $this->assertSame(['id' => 'abc-123'], $match?->params);
    }

    public function testMethodAliasCrossesPathAliasesRegardlessOfOrder(): void
    {
        $routeA = new Route(Http::REQUEST_METHOD_GET, '/a');
        $routeA
            ->alias('/a-old')
            ->aliasMethod(Http::REQUEST_METHOD_POST);

        Router::addRoute($routeA);

        $routeB = new Route(Http::REQUEST_METHOD_GET, '/b');
        $routeB
            ->aliasMethod(Http::REQUEST_METHOD_POST)
            ->alias('/b-old');

        Router::addRoute($routeB);

        $this->assertEquals($routeA, Router::match(Http::REQUEST_METHOD_POST, '/a')?->route);
        $this->assertEquals($routeA, Router::match(Http::REQUEST_METHOD_POST, '/a-old')?->route);
        $this->assertEquals($routeB, Router::match(Http::REQUEST_METHOD_POST, '/b')?->route);
        $this->assertEquals($routeB, Router::match(Http::REQUEST_METHOD_POST, '/b-old')?->route);
    }

    public function testCannotRegisterDuplicateMethodAlias(): void
    {
        $routePOST = new Route(Http::REQUEST_METHOD_POST, '/userinfo');
        Router::addRoute($routePOST);

        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/userinfo');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Route for (POST:userinfo) already registered.');
        $routeGET->aliasMethod(Http::REQUEST_METHOD_POST);
    }

    public function testCanOverrideMethodAlias(): void
    {
        Router::setAllowOverride(true);

        try {
            $routePOST = new Route(Http::REQUEST_METHOD_POST, '/userinfo');
            Router::addRoute($routePOST);

            $routeGET = new Route(Http::REQUEST_METHOD_GET, '/userinfo');
            Router::addRoute($routeGET);
            $routeGET->aliasMethod(Http::REQUEST_METHOD_POST);
            $routeGET->aliasMethod(Http::REQUEST_METHOD_POST);

            $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_POST, '/userinfo')?->route);
            $this->assertSame([Http::REQUEST_METHOD_POST], $routeGET->getAliasMethods());
        } finally {
            Router::setAllowOverride(false);
        }
    }

    public function testCannotRegisterMethodAliasForUnknownMethod(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/userinfo');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method (TRACE) not supported.');
        $route->aliasMethod('TRACE');
    }

    public function testCannotRegisterMethodAliasOnWildcardRoute(): void
    {
        $route = new Route('', '');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method aliases are not supported for the wildcard route.');
        $route->aliasMethod(Http::REQUEST_METHOD_POST);
    }

    public function testCanMatchFilename(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/robots.txt');

        Router::addRoute($routeGET);
        $this->assertEquals($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/robots.txt')?->route);
    }

    public function testCannotFindUnknownRouteByPath(): void
    {
        $this->assertNull(Router::match(Http::REQUEST_METHOD_GET, '/404')?->route);
    }

    public function testCannotFindUnknownRouteByMethod(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/404');

        Router::addRoute($route);

        $this->assertEquals($route, Router::match(Http::REQUEST_METHOD_GET, '/404')?->route);

        $this->assertNull(Router::match(Http::REQUEST_METHOD_POST, '/404')?->route);
    }
}
