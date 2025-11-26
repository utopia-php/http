<?php

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

        $this->assertSame($routeIndex, Router::match(Http::REQUEST_METHOD_GET, '/'));
        $this->assertSame($routeAbout, Router::match(Http::REQUEST_METHOD_GET, '/about'));
        $this->assertSame($routeAboutMe, Router::match(Http::REQUEST_METHOD_GET, '/about/me'));
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

        $this->assertSame($routeBlog, Router::match(Http::REQUEST_METHOD_GET, '/blog'));
        $this->assertSame($routeBlogAuthors, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors'));
        $this->assertSame($routeBlogAuthorsComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors/comments'));
        $this->assertSame($routeBlogPost, Router::match(Http::REQUEST_METHOD_GET, '/blog/test'));
        $this->assertSame($routeBlogPostComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments'));
        $this->assertSame($routeBlogPostCommentsSingle, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments/0'));
        $this->assertSame($routeBlogPostCommentsSingle, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments/:comment'));
    }

    public function testCanMatchUrlWithWildcard(): void
    {
        $routeIndex = new Route('GET', '/');
        $routeAbout = new Route('GET', '/about');
        $routeAboutWildcard = new Route('GET', '/about/*');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutWildcard);

        $this->assertSame($routeIndex, Router::match('GET', '/'));
        $this->assertSame($routeAbout, Router::match('GET', '/about'));
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/me'));
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/you'));
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/me/myself/i'));
    }

    public function testCanMatchHttpMethod(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/');
        $routePOST = new Route(Http::REQUEST_METHOD_POST, '/');

        Router::addRoute($routeGET);
        Router::addRoute($routePOST);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/'));
        $this->assertSame($routePOST, Router::match(Http::REQUEST_METHOD_POST, '/'));

        $this->assertNotEquals($routeGET, Router::match(Http::REQUEST_METHOD_POST, '/'));
        $this->assertNotEquals($routePOST, Router::match(Http::REQUEST_METHOD_GET, '/'));
    }

    public function testCanMatchAlias(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias')
            ->alias('/alias2');

        Router::addRoute($routeGET);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2'));
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

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/invite'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/login'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/recover'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console/lorem/ipsum/dolor'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/auth/lorem/ipsum'));
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/register/lorem/ipsum'));
    }

    public function testCanMatchFilename(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/robots.txt');

        Router::addRoute($routeGET);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/robots.txt'));
    }

    public function testCannotFindUnknownRouteByPath(): void
    {
        $this->assertNull(Router::match(Http::REQUEST_METHOD_GET, '/404'));
    }

    public function testCannotFindUnknownRouteByMethod(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/404');

        Router::addRoute($route);

        $this->assertSame($route, Router::match(Http::REQUEST_METHOD_GET, '/404'));

        $this->assertNull(Router::match(Http::REQUEST_METHOD_POST, '/404'));
    }
}
