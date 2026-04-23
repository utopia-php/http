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

        $this->assertSame($routeIndex, Router::match(Http::REQUEST_METHOD_GET, '/')->route);
        $this->assertSame($routeAbout, Router::match(Http::REQUEST_METHOD_GET, '/about')->route);
        $this->assertSame($routeAboutMe, Router::match(Http::REQUEST_METHOD_GET, '/about/me')->route);
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

        $this->assertSame($routeBlog, Router::match(Http::REQUEST_METHOD_GET, '/blog')->route);
        $this->assertSame($routeBlogAuthors, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors')->route);
        $this->assertSame($routeBlogAuthorsComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/authors/comments')->route);
        $this->assertSame($routeBlogPost, Router::match(Http::REQUEST_METHOD_GET, '/blog/test')->route);
        $this->assertSame($routeBlogPostComments, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments')->route);
        $this->assertSame($routeBlogPostCommentsSingle, Router::match(Http::REQUEST_METHOD_GET, '/blog/test/comments/:comment')->route);
    }

    public function testCanMatchUrlWithWildcard(): void
    {
        $routeIndex = new Route('GET', '/');
        $routeAbout = new Route('GET', '/about');
        $routeAboutWildcard = new Route('GET', '/about/*');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutWildcard);

        $this->assertSame($routeIndex, Router::match('GET', '/')->route);
        $this->assertSame($routeAbout, Router::match('GET', '/about')->route);
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/me')->route);
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/you')->route);
        $this->assertSame($routeAboutWildcard, Router::match('GET', '/about/me/myself/i')->route);
    }

    public function testCanMatchHttpMethod(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/');
        $routePOST = new Route(Http::REQUEST_METHOD_POST, '/');

        Router::addRoute($routeGET);
        Router::addRoute($routePOST);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/')->route);
        $this->assertSame($routePOST, Router::match(Http::REQUEST_METHOD_POST, '/')->route);

        $this->assertNotSame($routeGET, Router::match(Http::REQUEST_METHOD_POST, '/')->route);
        $this->assertNotSame($routePOST, Router::match(Http::REQUEST_METHOD_GET, '/')->route);
    }

    public function testCanMatchAlias(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias')
            ->alias('/alias2');

        Router::addRoute($routeGET);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2')->route);
    }

    public function testCanMatchMultipleAliases(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias1')
            ->alias('/alias2')
            ->alias('/alias3');

        Router::addRoute($routeGET);

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/target')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias1')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias2')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/alias3')->route);
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

        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/invite')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/login')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/recover')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/console/lorem/ipsum/dolor')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/auth/lorem/ipsum')->route);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/register/lorem/ipsum')->route);
    }

    public function testCanMatchFilename(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/robots.txt');

        Router::addRoute($routeGET);
        $this->assertSame($routeGET, Router::match(Http::REQUEST_METHOD_GET, '/robots.txt')->route);
    }

    public function testCannotFindUnknownRouteByPath(): void
    {
        $this->assertNull(Router::match(Http::REQUEST_METHOD_GET, '/404'));
    }

    public function testCannotFindUnknownRouteByMethod(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/404');

        Router::addRoute($route);

        $this->assertSame($route, Router::match(Http::REQUEST_METHOD_GET, '/404')->route);

        $this->assertNull(Router::match(Http::REQUEST_METHOD_POST, '/404'));
    }
}
