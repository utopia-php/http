<?php

declare(strict_types=1);

namespace Utopia\Http;

use PHPUnit\Framework\TestCase;
use Utopia\Http\Adapter\FPM\Request;

final class RouterTest extends TestCase
{
    public function tearDown(): void
    {
        Router::reset();
    }

    /**
     * Test helper: drive Router::matchRequest with a method + path, returning
     * the matched Route (or null). Keeps the existing test cases readable
     * now that `Router::match(string, string)` is no longer public.
     */
    private function match(string $method, string $path): ?Route
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;

        return Router::matchRequest(new Request())?->route;
    }

    public function testCanMatchUrl(): void
    {
        $routeIndex = new Route(Http::REQUEST_METHOD_GET, '/');
        $routeAbout = new Route(Http::REQUEST_METHOD_GET, '/about');
        $routeAboutMe = new Route(Http::REQUEST_METHOD_GET, '/about/me');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutMe);

        $this->assertEquals($routeIndex, $this->match(Http::REQUEST_METHOD_GET, '/'));
        $this->assertEquals($routeAbout, $this->match(Http::REQUEST_METHOD_GET, '/about'));
        $this->assertEquals($routeAboutMe, $this->match(Http::REQUEST_METHOD_GET, '/about/me'));
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

        $this->assertEquals($routeBlog, $this->match(Http::REQUEST_METHOD_GET, '/blog'));
        $this->assertEquals($routeBlogAuthors, $this->match(Http::REQUEST_METHOD_GET, '/blog/authors'));
        $this->assertEquals($routeBlogAuthorsComments, $this->match(Http::REQUEST_METHOD_GET, '/blog/authors/comments'));
        $this->assertEquals($routeBlogPost, $this->match(Http::REQUEST_METHOD_GET, '/blog/test'));
        $this->assertEquals($routeBlogPostComments, $this->match(Http::REQUEST_METHOD_GET, '/blog/test/comments'));
        $this->assertEquals($routeBlogPostCommentsSingle, $this->match(Http::REQUEST_METHOD_GET, '/blog/test/comments/:comment'));
    }

    public function testCanMatchUrlWithWildcard(): void
    {
        $routeIndex = new Route('GET', '/');
        $routeAbout = new Route('GET', '/about');
        $routeAboutWildcard = new Route('GET', '/about/*');

        Router::addRoute($routeIndex);
        Router::addRoute($routeAbout);
        Router::addRoute($routeAboutWildcard);

        $this->assertEquals($routeIndex, $this->match('GET', '/'));
        $this->assertEquals($routeAbout, $this->match('GET', '/about'));
        $this->assertEquals($routeAboutWildcard, $this->match('GET', '/about/me'));
        $this->assertEquals($routeAboutWildcard, $this->match('GET', '/about/you'));
        $this->assertEquals($routeAboutWildcard, $this->match('GET', '/about/me/myself/i'));
    }

    public function testCanMatchHttpMethod(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/');
        $routePOST = new Route(Http::REQUEST_METHOD_POST, '/');

        Router::addRoute($routeGET);
        Router::addRoute($routePOST);

        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/'));
        $this->assertEquals($routePOST, $this->match(Http::REQUEST_METHOD_POST, '/'));

        $this->assertNotEquals($routeGET, $this->match(Http::REQUEST_METHOD_POST, '/'));
        $this->assertNotEquals($routePOST, $this->match(Http::REQUEST_METHOD_GET, '/'));
    }

    public function testCanMatchAlias(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias')
            ->alias('/alias2');

        Router::addRoute($routeGET);

        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/target'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/alias'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/alias2'));
    }

    public function testCanMatchMultipleAliases(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/target');
        $routeGET
            ->alias('/alias1')
            ->alias('/alias2')
            ->alias('/alias3');

        Router::addRoute($routeGET);

        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/target'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/alias1'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/alias2'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/alias3'));
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

        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/console'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/invite'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/login'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/recover'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/console/lorem/ipsum/dolor'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/auth/lorem/ipsum'));
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/register/lorem/ipsum'));
    }

    public function testCanMatchFilename(): void
    {
        $routeGET = new Route(Http::REQUEST_METHOD_GET, '/robots.txt');

        Router::addRoute($routeGET);
        $this->assertEquals($routeGET, $this->match(Http::REQUEST_METHOD_GET, '/robots.txt'));
    }

    public function testCannotFindUnknownRouteByPath(): void
    {
        $this->assertNull($this->match(Http::REQUEST_METHOD_GET, '/404'));
    }

    public function testCannotFindUnknownRouteByMethod(): void
    {
        $route = new Route(Http::REQUEST_METHOD_GET, '/404');

        Router::addRoute($route);

        $this->assertEquals($route, $this->match(Http::REQUEST_METHOD_GET, '/404'));

        $this->assertNull($this->match(Http::REQUEST_METHOD_POST, '/404'));
    }
}
