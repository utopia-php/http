<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
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

        $this->assertSame($routeIndex, Router::match(Http::REQUEST_METHOD_GET, '/')[0] ?? null);
        $this->assertSame($routeAbout, Router::match(Http::REQUEST_METHOD_GET, '/about')[0] ?? null);
        $this->assertSame($routeAboutMe, Router::match(Http::REQUEST_METHOD_GET, '/about/me')[0] ?? null);
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

        $this->assertSame($routeBlog, Router::match(Http::REQUEST_METHOD_GET, '/blog')[0] ?? null);
        $this->assertSame($routeBlogAuthors, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors')[0] ?? null);
        $this->assertSame($routeBlogAuthorsComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors/comments')[0] ?? null);
        $this->assertSame($routeBlogPost, Router::match(Http::REQUEST_METHOD_GET, '/blog/test')[0] ?? null);
        $this->assertSame($routeBlogPostComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments')[0] ?? null);
        $this->assertSame($routeBlogPostCommentsSingle, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments/:comment')[0] ?? null);
    }

    public function testCanMatchUrlWithWildcard(): void
    {
        $routeIndex = new Route('GET', '/');
        $routeAbout = new Route('GET', '/about');
        $routeAboutWildcard = new Route('GET', '/about/*');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutWildcard);

        $this->assertSame($routeIndex, Router::match('GET', '/')[0] ?? null);
        $this->assertSame($routeAbout, Router::match('GET', '/about')[0] ?? null);
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/me')[0] ?? null);
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/you')[0] ?? null);
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/me/myself/i')[0] ?? null);
    }

    public function testCanMatchHttpMethod(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/');
        $routePOST = new Route(Http::REQUEST_METHOD_POST, '/');

        Router::addRoute($routeGET);
        Router::addRoute($routePOST);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/')[0] ?? null);
        $this->assertSame($routePOST, Router::match(Http::REQUEST_METHOD_POST, '/')[0] ?? null);

        $this->assertNotSame($routeGET, Router::match(Http::REQUEST_METHOD_POST, '/')[0]);
        $this->assertNotSame($routePOST, Router::match(Http::REQUEST_METHOD_GET, '/')[0]);
    }

    public function testCanMatchAlias(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias')
            ->alias('/alias2');

        Router::addRoute($routeGET);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2')[0] ?? null);
    }

    public function testCanMatchMultipleAliases(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias1')
            ->alias('/alias2')
            ->alias('/alias3');

        Router::addRoute($routeGET);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias1')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias3')[0] ?? null);
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

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/invite')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/login')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/recover')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console/lorem/ipsum/dolor')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/auth/lorem/ipsum')[0] ?? null);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/register/lorem/ipsum')[0] ?? null);
    }

    public function testCanMatchFilename(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/robots.txt');

        Router::addRoute($routeGET);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/robots.txt')[0] ?? null);
    }

    public function testCannotFindUnknownRouteByPath(): void
    {
        $this->assertNull(Router::match(Http::REQUEST_METHOD_GET, '/404'));
    }

    public function testCannotFindUnknownRouteByMethod(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/404');

        Router::addRoute($route);

        $this->assertSame($route, Router::match(Http::REQUEST_METHOD_GET, '/404')[0] ?? null);

        $this->assertNull(Router::match(Http::REQUEST_METHOD_POST, '/404'));
    }
}
